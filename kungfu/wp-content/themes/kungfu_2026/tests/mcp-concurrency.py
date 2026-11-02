#!/usr/bin/env python3
"""Two independent WP processes race on one local-only draft. Requires Docker."""
import json
import subprocess
import time

BOOT = '''require '/var/www/html/wp-load.php';
if (!in_array(wp_parse_url(home_url(), PHP_URL_HOST), ['localhost','127.0.0.1'], true)) { throw new RuntimeException('Local tests only'); }
$users = get_users(['role'=>'administrator','number'=>1]); wp_set_current_user($users[0]->ID);
'''

def command(code):
    return ['docker', 'compose', 'exec', '-T', 'wordpress', 'php', '-r', BOOT + code]

def run(code):
    p = subprocess.run(command(code), text=True, capture_output=True, check=True)
    return p.stdout.strip()

post_id = None
try:
    data = json.loads(run('''$id = wp_insert_post(['post_title'=>'MCP concurrency draft fixture','post_status'=>'draft','post_content'=>"<!-- wp:paragraph -->\\n<p>Original</p>\\n<!-- /wp:paragraph -->"]);
    $snap = kungfu_2026_mcp_editing_snapshot(get_post($id));
    echo wp_json_encode(['post_id'=>$id, 'content_revision'=>$snap['content_revision'], 'block_id'=>$snap['blocks'][0]['block_id'], 'paragraphs'=>['Concurrent insertion'], 'dry_run'=>false]);'''))
    post_id = int(data['post_id'])
    # Passing JSON through argv avoids embedding data in PHP source.
    import base64
    encoded = base64.b64encode(json.dumps(data).encode()).decode()
    args_php = "$edit = json_decode(base64_decode('" + encoded + "'), true);\n"
    writer_a = subprocess.Popen(command(args_php + '''$held = false;
    add_filter('wp_insert_post_data', function($data, $postarr) use (&$held, $edit) {
        if (!$held && ($postarr['ID'] ?? 0) == $edit['post_id'] && $data['post_type'] === 'post') {
            $held = true; echo "LOCKED\\n"; flush(); usleep(1800000);
        } return $data;
    }, 10, 2);
    echo wp_json_encode(kungfu_2026_mcp_edit_content($edit, 'insert_content'));'''), stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    assert writer_a.stdout.readline().strip() == 'LOCKED', 'Writer A must hold the row lock'
    started = time.monotonic()
    writer_b = json.loads(run(args_php + "echo wp_json_encode(kungfu_2026_mcp_edit_content($edit, 'insert_content'));"))
    elapsed = time.monotonic() - started
    out_a, err_a = writer_a.communicate(timeout=15)
    assert writer_a.returncode == 0, err_a
    result_a = json.loads(out_a)
    assert result_a['structuredContent']['applied'] is True, result_a
    assert writer_b['isError'] is True and 'Revision conflict' in writer_b['content'][0]['text'], writer_b
    assert elapsed > 1, f'Writer B must wait for row lock, observed {elapsed}'
    stored = json.loads(run(f'echo wp_json_encode(kungfu_2026_mcp_editing_snapshot(get_post({post_id})));'))
    assert len(stored['blocks']) == 2 and stored['status'] == 'draft', stored
    print('PASS: two concurrent writers; first applies once, second waits then rejects stale revision; draft preserved.')
finally:
    if post_id:
        run(f'wp_delete_post({post_id}, true);')
