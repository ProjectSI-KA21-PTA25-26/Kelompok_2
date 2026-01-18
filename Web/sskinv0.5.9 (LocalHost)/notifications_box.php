<?php
// Drop-in notification dropdown. Expects an active session and $database connection.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    return;
}

$notifApiPath = $notifApiPath ?? "notifications_api.php";

require_once(__DIR__ . "/notification_helper.php");

$useremail = $_SESSION['user'];
$initialNotifications = fetch_notifications_for($database, $useremail, 10);
$initialUnread = count_unread_for($database, $useremail);
$uid = "notif_" . substr(md5($useremail . microtime(true)), 0, 6);
?>
<?php if (!defined('NOTIF_BOX_CSS_LOADED')): ?>
<?php define('NOTIF_BOX_CSS_LOADED', true); ?>
<style>
.notif-wrapper{position:relative;display:inline-block;width:100%;}
.notif-bell{width:100%;border:1px solid #d9d9d9;background:#f9fafc;color:#1d1e1f;padding:8px 12px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:10px;font-weight:600;font-size:14px;}
.notif-bell:hover{background:#eef2ff;border-color:#c7d2fe;}
.notif-count{min-width:24px;height:24px;border-radius:12px;background:#ff6b6b;color:#fff;font-size:12px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;padding:0 7px;}
.notif-box{position:absolute;top:110%;left:0;right:0;z-index:20;background:#ffffff;border:1px solid #e5e7eb;box-shadow:0 10px 35px rgba(17,24,39,0.15);border-radius:10px;padding:12px;display:none;max-height:320px;}
.notif-box.visible{display:block;}
.notif-box-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.notif-title{font-weight:700;font-size:15px;margin:0;}
.notif-mark{background:none;border:none;color:#2563eb;font-weight:600;cursor:pointer;}
.notif-list{list-style:none;margin:0;padding:0;max-height:250px;overflow-y:auto;}
.notif-item{padding:8px 6px;border-bottom:1px solid #f1f5f9;}
.notif-item:last-child{border-bottom:none;}
.notif-item a{color:#0f172a;text-decoration:none;font-weight:600;}
.notif-item.unread{background:#f8fafc;}
.notif-meta{display:block;font-size:12px;color:#6b7280;margin-top:4px;}
</style>
<?php endif; ?>
<div class="notif-wrapper" id="<?php echo $uid; ?>_wrap" style="margin-top: 12px; margin-bottom: 8px;">
    <button class="notif-bell" type="button" id="<?php echo $uid; ?>_toggle">
        <span>Notifikasi</span>
        <span class="notif-count" id="<?php echo $uid; ?>_count"><?php echo intval($initialUnread); ?></span>
    </button>
    <div class="notif-box" id="<?php echo $uid; ?>_box">
        <div class="notif-box-header">
            <p class="notif-title">Kotak Notifikasi</p>
            <button class="notif-mark" type="button" id="<?php echo $uid; ?>_mark">Tandai sudah dibaca</button>
        </div>
        <ul class="notif-list" id="<?php echo $uid; ?>_list">
            <?php if (empty($initialNotifications)): ?>
                <li class="notif-item">Tidak ada notifikasi</li>
            <?php else: ?>
                <?php foreach ($initialNotifications as $n): ?>
                    <li class="notif-item <?php echo $n['is_read'] ? '' : 'unread'; ?>">
                        <?php if (!empty($n['url'])): ?>
                            <a href="<?php echo htmlspecialchars($n['url']); ?>"><?php echo htmlspecialchars($n['message']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($n['message']); ?>
                        <?php endif; ?>
                        <span class="notif-meta"><?php echo htmlspecialchars($n['created_at']); ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>
<script>
(function(){
    const apiUrl = "<?php echo $notifApiPath; ?>";
    const wrap = document.getElementById("<?php echo $uid; ?>_wrap");
    const toggle = document.getElementById("<?php echo $uid; ?>_toggle");
    const box = document.getElementById("<?php echo $uid; ?>_box");
    const listEl = document.getElementById("<?php echo $uid; ?>_list");
    const countEl = document.getElementById("<?php echo $uid; ?>_count");
    const markBtn = document.getElementById("<?php echo $uid; ?>_mark");

    function renderList(data){
        listEl.innerHTML = '';
        if(!data || data.length === 0){
            listEl.innerHTML = '<li class="notif-item">Tidak ada notifikasi</li>';
            return;
        }
        data.forEach(function(n){
            const li = document.createElement('li');
            li.className = 'notif-item ' + (n.is_read ? '' : 'unread');
            const meta = document.createElement('span');
            meta.className = 'notif-meta';
            meta.textContent = n.created_at || '';
            if(n.url){
                const a = document.createElement('a');
                a.href = n.url;
                a.textContent = n.message;
                li.appendChild(a);
            }else{
                li.textContent = n.message;
            }
            li.appendChild(meta);
            listEl.appendChild(li);
        });
    }

    function setCount(c){
        const val = parseInt(c || 0, 10);
        countEl.textContent = val;
        if(val === 0){
            countEl.style.background = '#cbd5e1';
            countEl.style.color = '#0f172a';
        }else{
            countEl.style.background = '#ff6b6b';
            countEl.style.color = '#fff';
        }
    }

    async function fetchNotifications(){
        try{
            const res = await fetch(apiUrl + '?action=list&limit=10', {credentials:'same-origin'});
            if(!res.ok) return;
            const data = await res.json();
            renderList(data.notifications || []);
            setCount(data.unread_count || 0);
        }catch(e){
            // silent fail on polling
        }
    }

    async function markAllRead(){
        try{
            await fetch(apiUrl + '?action=mark_all_read', {method:'POST', credentials:'same-origin'});
            await fetchNotifications();
        }catch(e){
            console.error(e);
        }
    }

    toggle.addEventListener('click', function(){
        box.classList.toggle('visible');
        if(box.classList.contains('visible')){
            fetchNotifications();
        }
    });

    document.addEventListener('click', function(e){
        if(!wrap.contains(e.target)){
            box.classList.remove('visible');
        }
    });

    markBtn.addEventListener('click', function(){
        markAllRead();
    });

    // start polling for realtime-ish updates
    setInterval(fetchNotifications, 8000);
})();
</script>
