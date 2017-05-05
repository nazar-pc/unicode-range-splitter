# Unicode range splitter for icon fonts
This is a simple tool for splitting your `*.woff2` icon font and corresponding `*.css` file into series of `*.woff2` files by unicode ranges and `*.css` file.

For instance, if you have a large font file (like beautiful [Font Awesome](http://fontawesome.io/)), you might not be entirely happy forcing your users to download all of the icons while your page only needs a few of them.
Using this tool you can split all of the icons into unicode ranges and put each range into separate file. When your browser tries to display an icon, it will only download a file which contains this icon, but not the rest of the files with icons that are not needed.

WOFF2 format is supported across all major evergreen browsers, so this tool only supports WOFF2 and drops any other formats from CSS file (if you absolutely need, feel free to customize source code).

# Usage
```
Splitter takes *.woff2 and *.css files and produces series of *.woff2 files and modified *.css file
Produced *.woff2 files are separated in such a way that each file will only contain at most CHARS_IN_RANGE glyphs (icons)
*.css will only contain references to newly created *.woff2 files, references to other formats will be removed entirely

CHARS_IN_RANGE env var can be used to specify how much glyphs (icons) should single file contain.
FONT_FILE_PREFIX env var can be used to specify prefix for the file name (`font-` means that `font-0.woff2`, `font-1.woff2` and so on files will be created).

Examples:
  docker run --rm -it --volume=/path/to/font.woff2:/font.woff2 --volume=/path/to/style.css:/style.css --volume=/path/to/unicode-ranged:/output nazarpc/unicode-range-splitter
  docker run --rm -it  -e "CHARS_IN_RANGE=50" -e "FONT_FILE_PREFIX=font-" --volume=/path/to/font.woff2:/font.woff2 --volume=/path/to/style.css:/style.css --volume=/path/to/unicode-ranged:/output nazarpc/unicode-range-splitter
```

# License
Public Domain
