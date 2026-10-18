"""Admin batch generation/export and permission checks on disposable localhost."""
import csv
import http.cookiejar
import io
import json
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
base = 'http://localhost:8097/'

def call(opener, path, data=None):
    try:
        result = opener.open(base + path, urllib.parse.urlencode(data).encode() if data is not None else None)
        return result.status, result.read().decode('utf-8-sig'), result.geturl()
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode('utf-8-sig'), error.url

def login(user, password):
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    call(opener, 'wp-login.php')
    call(opener, 'wp-login.php', {'log': user, 'pwd': password, 'testcookie': '1', 'redirect_to': base})
    return opener

admin = login('test-admin', 'local-test-admin-only')
status, html, _ = call(admin, 'wp-admin/edit.php?post_type=access-codes&page=pyropaths-codes')
assert status == 200 and 'Generate codes' in html
nonce = re.findall(r'name="_wpnonce" value="([^"]+)"', html)[0]
product = re.search(r'<select id="product".*?<option value="(\d+)"', html, re.S).group(1)
status, html, url = call(admin, 'wp-admin/admin-post.php', {'action': 'pyropaths_generate', '_wpnonce': nonce, 'product': product, 'quantity': 1, 'batch': 'http-admin-test'})
assert status == 200 and 'Batch saved:' in html
batch = urllib.parse.parse_qs(urllib.parse.urlparse(url).query)['batch'][0]
print('PASS: administrator generates one code through admin form')
nonces = re.findall(r'name="_wpnonce" value="([^"]+)"', html)
status, output, _ = call(admin, 'wp-admin/admin-post.php', {'action': 'pyropaths_export', '_wpnonce': nonces[1], 'batch': batch})
rows = list(csv.reader(io.StringIO(output)))
assert status == 200 and rows[0] == ['code', 'product_id', 'product', 'tier', 'batch', 'status'] and len(rows) == 2 and rows[1][5] == 'Available'
print('PASS: administrator exports generated batch as valid CSV')
status, _, _ = call(admin, 'wp-admin/admin-post.php', {'action': 'pyropaths_generate', '_wpnonce': 'invalid', 'product': product, 'quantity': 1})
assert status == 403
print('PASS: generation rejects invalid nonce')
subscriber = login('http-yellow', 'local-http-only-test')
status, _, _ = call(subscriber, 'wp-admin/admin-post.php', {'action': 'pyropaths_export', '_wpnonce': nonces[1], 'batch': batch})
assert status == 403
print('PASS: subscribers cannot export access codes')
status, _, _ = call(subscriber, 'wp-admin/admin-post.php', {'action': 'pyropaths_generate', '_wpnonce': nonce, 'product': product, 'quantity': 1})
assert status == 403
print('PASS: subscribers cannot generate access codes')
