"""Run after integration.php: simultaneous attempts must have exactly one winner."""
import concurrent.futures
import subprocess

def redeem(index):
    return subprocess.check_output(['docker', 'compose', '-f', 'tests/compose.yml', 'exec', '-T', 'wordpress', 'php', 'wp-content/plugins/hotlines-onboarding/tests/race.php', str(index)]).decode().strip()

with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
    results = list(pool.map(redeem, [0, 1]))
assert sorted(results) == ['redeemed', 'rejected'], results
print('PASS: simultaneous redemption has exactly one winner')
