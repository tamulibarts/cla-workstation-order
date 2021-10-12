# Git Bash loop to remove files from entire repository history.
# Probably not the cleanest approach.
files=(file-name-one.txt, file-name-two.js, file-name-three.sql)
for file in ${files[@]}; do
	git filter-branch --force --index-filter \
	"git rm --cached --ignore-unmatch $file" \
	--prune-empty --tag-name-filter cat -- --all
done
