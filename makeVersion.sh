#!/bin/bash

DATE=`date +"%Y-%m-%d"`
HASH=`find . -not -path ./.git* -type f -print0 | sort -z | xargs -0 sha1sum | sha1sum | tail -n 1 | cut -d " " -f 1`
SHORTHASH=`echo ${HASH: -5}`
VERSION="$DATE-$SHORTHASH"

echo $HASH > HASH
echo -e "HASH generated:\t\t$HASH"
echo $VERSION > VERSION
echo -e "VERSION generated:\t$VERSION"

cat > common/version.php <<EOF
<?php
\$version = array(
	 'String' => "$VERSION",
	 'Date' => "$DATE",
);

global \$version;
?>
EOF

git add VERSION HASH
git add common/version.php
git commit -m "release $VERSION"
echo -e "new commit generated"
