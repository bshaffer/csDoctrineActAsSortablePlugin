echo "Cloning symfony and checking out tag/branch/commit"
git clone git://github.com/symfony/symfony1.git lib/vendor/symfony

cd lib/vendor/symfony

git fetch --all

git checkout $SYMFONY_REF
