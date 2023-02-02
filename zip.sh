#!/bin/bash

cd ..
rm -f   ya-wordpress-plugin-template.zip
zip -r  ya-wordpress-plugin-template.zip \
        ya-wordpress-plugin-template \
    -x "ya-wordpress-plugin-template/*.sh" \
    -x "ya-wordpress-plugin-template/.git/*" \
    -x "ya-wordpress-plugin-template/.gitignore" \
    -x "*/.DS_Store"
cd -

