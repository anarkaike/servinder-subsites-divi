#!/usr/bin/env bash
set -euo pipefail

repo_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
plugin_slug="servinder-subsites-divi"
remote_host="${SERVINDER_WP_HOST:-root@vps.servinder.com.br}"
container_name="${SERVINDER_WP_CONTAINER:-wordpress-smjeqwsb8gswpmabmy3u6oju}"
network_url="${SERVINDER_WP_NETWORK_URL:-https://wp.servinder.com.br/}"
smoke_url="${SERVINDER_WP_SMOKE_URL:-$network_url}"
wp_cli_user="${SERVINDER_WP_CLI_USER:-1}"
release_stamp="$(date -u +%Y%m%dT%H%M%SZ)"
remote_release_dir="/root/wp-releases/$plugin_slug-$release_stamp"
zip_name="$plugin_slug.zip"
local_release_dir="$repo_root/release"
plugin_mutated=false
plugin_backup_available=false
release_complete=false
temp_dir=""

rollback_plugin() {
	if [[ "$plugin_mutated" != true || "$release_complete" == true ]]; then
		return
	fi
	if [[ "$plugin_backup_available" == true ]]; then
		ssh "$remote_host" "docker exec '$container_name' rm -rf '/var/www/html/wp-content/plugins/$plugin_slug' && docker cp '$remote_release_dir/$plugin_slug-before.tgz' '$container_name:/tmp/$plugin_slug-before.tgz' && docker exec '$container_name' tar -xzf '/tmp/$plugin_slug-before.tgz' -C /var/www/html/wp-content/plugins" >/dev/null 2>&1 || true
	else
		ssh "$remote_host" "/usr/local/bin/wp plugin deactivate '$plugin_slug' --network --skip-plugins='$plugin_slug' --url='$network_url' >/dev/null 2>&1 || true; docker exec '$container_name' rm -rf '/var/www/html/wp-content/plugins/$plugin_slug'" >/dev/null 2>&1 || true
	fi
}

cleanup() {
	if [[ -n "$temp_dir" ]]; then rm -rf "$temp_dir"; fi
	ssh "$remote_host" "docker exec '$container_name' rm -rf '/tmp/$plugin_slug-release' && docker exec '$container_name' rm -f '/tmp/$zip_name' '/tmp/$plugin_slug-smoke.php'" >/dev/null 2>&1 || true
}

trap 'rollback_plugin; cleanup' EXIT

cd "$repo_root"
if [[ "$(git branch --show-current)" != "main" ]]; then
	printf 'Release requires main.\n' >&2
	exit 1
fi
if [[ -n "$(git status --porcelain)" ]]; then
	printf 'Working tree must be clean before release.\n' >&2
	exit 1
fi
head_commit="$(git rev-parse HEAD)"
remote_commit="$(git ls-remote origin refs/heads/main | awk '{print $1}')"
if [[ "$remote_commit" != "$head_commit" ]]; then
	printf 'Local HEAD must match origin/main before release.\n' >&2
	exit 1
fi
python3 -m json.tool "$repo_root/visual-builder/src/module.json" >/dev/null

temp_dir="$(mktemp -d)"
mkdir -p "$temp_dir/$plugin_slug" "$local_release_dir"
git archive HEAD | tar -xf - -C "$temp_dir/$plugin_slug"
rm -f "$local_release_dir/$zip_name"
(cd "$temp_dir" && zip -qr "$local_release_dir/$zip_name" "$plugin_slug/servinder-subsites-divi.php" "$plugin_slug/src" "$plugin_slug/visual-builder/src/module.json" "$plugin_slug/visual-builder/build/servinder-subsites-divi.js" "$plugin_slug/README.md" "$plugin_slug/CHANGELOG.md")
unzip -t "$local_release_dir/$zip_name" >/dev/null
package_listing="$(unzip -Z1 "$local_release_dir/$zip_name")"
for required_path in "$plugin_slug/servinder-subsites-divi.php" "$plugin_slug/src/Plugin.php" "$plugin_slug/src/Divi5/ConditionalContentModule.php" "$plugin_slug/visual-builder/src/module.json" "$plugin_slug/visual-builder/build/servinder-subsites-divi.js"; do
	if ! printf '%s\n' "$package_listing" | grep -Fqx "$required_path"; then
		printf 'Release package is missing %s.\n' "$required_path" >&2
		exit 1
	fi
done
if printf '%s\n' "$package_listing" | grep -Eq '(^|/)(tests|docs|node_modules|\.git|\.env)(/|$)'; then
	printf 'Release package contains development or environment files.\n' >&2
	exit 1
fi

local_hash="$(shasum -a 256 "$local_release_dir/$zip_name" | awk '{print $1}')"
ssh "$remote_host" "install -d -m 0700 '$remote_release_dir'"
scp "$local_release_dir/$zip_name" "$remote_host:$remote_release_dir/$zip_name"
scp "$repo_root/tests/smoke.php" "$remote_host:$remote_release_dir/$plugin_slug-smoke.php"
remote_hash="$(ssh "$remote_host" "sha256sum '$remote_release_dir/$zip_name' | awk '{print \$1}'")"
if [[ "$local_hash" != "$remote_hash" ]]; then
	printf 'Package SHA-256 mismatch.\n' >&2
	exit 1
fi

docker run --rm -v "$temp_dir/$plugin_slug:/src" -w /src composer:2 sh -lc 'find src tests -name "*.php" -print0 | xargs -0 -n1 php -l; php -l servinder-subsites-divi.php'

if ssh "$remote_host" "/usr/local/bin/wp plugin is-installed '$plugin_slug' --skip-plugins='$plugin_slug' --url='$network_url' >/dev/null 2>&1"; then
	ssh "$remote_host" "docker exec '$container_name' tar -czf '/tmp/$plugin_slug-before.tgz' -C /var/www/html/wp-content/plugins '$plugin_slug' && docker cp '$container_name:/tmp/$plugin_slug-before.tgz' '$remote_release_dir/$plugin_slug-before.tgz'"
	plugin_backup_available=true
fi

plugin_mutated=true
ssh "$remote_host" "docker cp '$remote_release_dir/$zip_name' '$container_name:/tmp/$zip_name' && /usr/local/bin/wp plugin install '/tmp/$zip_name' --force --activate-network --skip-plugins='$plugin_slug' --url='$network_url' && docker cp '$remote_release_dir/$plugin_slug-smoke.php' '$container_name:/tmp/$plugin_slug-smoke.php'"
ssh "$remote_host" "/usr/local/bin/wp eval-file '/tmp/$plugin_slug-smoke.php' --user='$wp_cli_user' --url='$smoke_url'"

site_urls="$(ssh "$remote_host" "/usr/local/bin/wp site list --field=url --url='$network_url'")"
while IFS= read -r site_url; do
	[[ -n "$site_url" ]] || continue
	plugin_state="$(ssh "$remote_host" "/usr/local/bin/wp plugin list --field=status --name='$plugin_slug' --url='$site_url'")"
	if [[ "$plugin_state" != "active-network" ]]; then
		printf 'Expected network-active status for %s on %s, got: %s\n' "$plugin_slug" "$site_url" "${plugin_state:-missing}" >&2
		exit 1
	fi
	printf '%s: %s on %s\n' "$plugin_slug" "$plugin_state" "$site_url"
done <<< "$site_urls"

container_health="$(ssh "$remote_host" "docker inspect '$container_name' --format '{{.State.Health.Status}}'")"
if [[ "$container_health" != "healthy" ]]; then
	printf 'WordPress container is not healthy: %s\n' "$container_health" >&2
	exit 1
fi
http_code="$(curl -sS -o /dev/null -w '%{http_code}' https://guiagaysaopaulo.servinder.com.br/)"
if [[ "$http_code" != "200" ]]; then
	printf 'Guia Gay São Paulo returned HTTP %s.\n' "$http_code" >&2
	exit 1
fi

release_complete=true
printf 'Released %s from %s\nPackage: %s\nSHA-256: %s\nBlogs verified: %s\nContainer: %s\nGuia HTTP: %s\nBackup: %s/%s-before.tgz\n' "$plugin_slug" "$head_commit" "$local_release_dir/$zip_name" "$local_hash" "$(printf '%s\n' "$site_urls" | wc -l | tr -d ' ')" "$container_health" "$http_code" "$remote_release_dir" "$plugin_slug"
