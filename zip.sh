#!/bin/bash
export SLUG=ya-wordpress-plugin-template

cd ..
rm -f   ${SLUG}.zip
zip -r  ${SLUG}.zip \
        ${SLUG} \
    -x "${SLUG}/*.sh" \
    -x "${SLUG}/.git/*" \
    -x "${SLUG}/.gitignore" \
    -x "*/.DS_Store"
cd -

