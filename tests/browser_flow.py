"""Real HTTP checks against the disposable Docker site only."""
import http.cookiejar
import json
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

base = 'http://localhost:8097/'
state = json.loads(subprocess.check_output(['docker', 'compose', '-f', 'tests/compose.yml', 'exec', '-T', 'wordpress', 'php', 'wp-content/plugins/hotlines-onboarding/tests/http-state.php']))

def client():
    cookies = http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cookies)), cookies

def request(opener, path, values=None):
    try:
        response = opener.open(base + path, urllib.parse.urlencode(values).encode() if values is not None else None)
        return response.status, response.read().decode(), response.geturl()
    except urllib.error.HTTPError as error:
        return error.code, error.read().decode(), error.url

def page(name):
    return '?page_id=' + str(state['pages'][name])

def check(value, message):
    assert value, message
    print('PASS:', message)

opener, cookies = client()
status, html, _ = request(opener, page('registration'))
check(status == 200, 'registration form reachable')
nonce = re.search(r'name="hotlines_register_nonce" value="([^"]+)"', html).group(1)
email = f'http-registration-{time.time_ns()}@example.test'
values = dict(hotlines_register_nonce=nonce, first_name='Test', last_name='Owner', email=email, password='local-http-password', access_code='not-a-real-code')
_, html, _ = request(opener, page('registration'), values)
check('not found' in html, 'invalid-code error renders without redirect')
values['access_code'] = state['registration_code'].upper()
status, html, url = request(opener, page('registration'), values)
check(status == 200 and ('page_id=' + str(state['pages']['my-account'])) in url, 'new registration redirects to configured My Account')
check(any(cookie.name.startswith('wordpress_logged_in') for cookie in cookies), 'registration establishes authenticated cookie')
check('Register a product' in html, 'My Account includes additional-code form')
_, html, _ = request(opener, page('library'))
check('Mushroom template' in html and 'Frog template' not in html, 'registered customer sees only owned template via HTTP')
_, html, _ = request(opener, page('my-account'))
nonce = re.search(r'name="hotlines_register_nonce" value="([^"]+)"', html).group(1)
_, html, _ = request(opener, page('my-account'), {'hotlines_register_nonce': nonce, 'access_code': state['registration_code']})
check('already been used' in html or 'already been redeemed' in html, 'signed-in code reuse displays an error')

for tier, login in state['tiers'].items():
    opener, _ = client()
    request(opener, 'wp-login.php')
    request(opener, 'wp-login.php', {'log': login, 'pwd': 'local-http-only-test', 'wp-submit': 'Log In', 'testcookie': '1', 'redirect_to': base})
    for content_tier in ['yellow', 'orange', 'red']:
        _, html, _ = request(opener, page('test-' + content_tier))
        permitted = ['yellow', 'orange', 'red'].index(content_tier) <= ['yellow', 'orange', 'red'].index(tier)
        check(('Exclusive ' + content_tier + ' content' in html) == permitted, f'{tier} tier visibility for {content_tier} content')
opener, _ = client()
_, html, _ = request(opener, page('learn'))
check('Free videos for everyone' in html, 'free Learn content remains public')
_, html, _ = request(opener, page('library'))
check('log in' in html and 'Mushroom template' not in html, 'anonymous Library does not expose owned templates')
status, _, _ = request(opener, '?rest_route=/wp/v2/access-codes')
check(status == 404, 'public access-code REST route is absent')
print('HTTP checks complete.')
