#!/bin/bash

CDIR=$(pwd)
BNAME=$(basename $CDIR)

cd ..
rm -f   $BNAME.zip
zip -r  $BNAME.zip \
        $BNAME \
    -x "$BNAME/build.sh" \
    -x "$BNAME/incver.php" \
    -x "$BNAME/zip.sh" \
    -x "$BNAME/.git/*" \
    -x "$BNAME/archive/*" \
    -x "*/.DS_Store"
cd -

