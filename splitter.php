<?php
/**
 * @author  Nazar Mokrynskyi <nazar@mokrynskyi.com>
 * @license Public Domain
 */
if (!file_exists('/style.css') || !is_dir('/output')) {
	echo <<<HELP
Splitter takes *.woff2 and *.css files and produces series of *.woff2 files and modified *.css file
Produced *.woff2 files are separated in such a way that each file will only contain at most CHARS_IN_RANGE glyphs (icons)
*.css will only contain references to newly created *.woff2 files, references to other formats will be removed entirely

CHARS_IN_RANGE env var can be used to specify how much glyphs (icons) should single file contain.
FONT_FILE_PREFIX env var can be used to specify prefix for the file name (`font-` means that `font-0.woff2`, `font-1.woff2` and so on files will be created).

Examples:
  docker run --rm -it --volume=/path/to/font.woff2:/font.woff2 --volume=/path/to/style.css:/style.css --volume=/path/to/unicode-ranged:/output nazarpc/unicode-range-splitter
  docker run --rm -it  -e "CHARS_IN_RANGE=50" -e "FONT_FILE_PREFIX=font-" --volume=/path/to/font.woff2:/font.woff2 --volume=/path/to/style.css:/style.css --volume=/path/to/unicode-ranged:/output nazarpc/unicode-range-splitter
HELP;
}

$css = file_get_contents('/style.css');

$font_face                 = explode('@font-face', $css, 2)[1];
$font_face                 = explode('}', $font_face, 2)[0];
$font_face_for_replacement = "@font-face$font_face}";
$font_face                 = explode('{', $font_face, 2)[1];
$font_face                 = explode(';', $font_face);
$font_face                 = array_map('trim', $font_face);
$font_face                 = array_filter(
	$font_face,
	function ($line) {
		return strpos($line, 'src') !== 0;
	}
);

preg_match_all('/content.*:.*([a-f0-9]+?).*/Uims', $css, $unicode_characters);
$unicode_characters = array_unique($unicode_characters[1]);
sort($unicode_characters);

copy('/font.woff2', '/tmp/font.woff2');
// glyphIgo (python-fontforge actually) doesn't work with *.woff2, so let's decompress it first
system('woff2_decompress /tmp/font.woff2') !== false || exit;

$new_font_face = '';
$count         = count($unicode_characters) / 100;
for ($i = 0; $i < $count; ++$i) {
	$range = array_slice($unicode_characters, $i * $_ENV['CHARS_IN_RANGE'], $_ENV['CHARS_IN_RANGE']);

	$font_face_local = implode(
		';',
		array_merge(
			$font_face,
			[
				"unicode-range: U+$range[0]-{$range[count($range)-1]}",
				"src: url($_ENV[FONT_FILE_PREFIX]$i.woff2) format('woff2')"
			]
		)
	);
	$new_font_face   .= "@font-face { $font_face_local }";

	$binary = '';
	foreach ($range as $r) {
		$r      = str_pad($r, 4, '0', STR_PAD_LEFT);
		$binary .= "\u$r";
	}
	$binary = json_decode("\"$binary\"");
	file_put_contents('/tmp/characters', $binary);
	system("glyphIgo subset -f /tmp/font.ttf --plain /tmp/characters -o /output/$_ENV[FONT_FILE_PREFIX]$i.ttf") !== false || exit;
	// We want *.woff2 eventually, so let's compress it and remove original *.ttf file
	system("woff2_compress /output/$_ENV[FONT_FILE_PREFIX]$i.ttf") !== false || exit;
	system("rm /output/$_ENV[FONT_FILE_PREFIX]$i.ttf") !== false || exit;
}
file_put_contents('/output/style.css', str_replace($font_face_for_replacement, $new_font_face, $css));
