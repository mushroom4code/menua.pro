</div>
</div>
</div>
</div> 
<? if (1 && !in_array($_USER['id'], [199, 176])) { ?>
	<script type="text/javascript" >


		(function (m, e, t, r, i, k, a) {
			m[i] = m[i] || function () {
				(m[i].a = m[i].a || []).push(arguments)
			};
			m[i].l = 1 * new Date();
			k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
		})
				(window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
		ym(66587317, "init", {
			params: {name: "<?= $_USER['lname'] . ' ' . $_USER['fname']; ?>"},
			clickmap: true,
			trackLinks: true,
			accurateTrackBounce: true,
			webvisor: true
		});
	</script>
<? } ?>
<div class="QR" style="display: none;">
	<img src="/sync/3rdparty/phpqrcode/page.php?path=<?= urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&size=3">		
</div>

</body>
</html><?
$PGT = (microtime(1) - $PGT_START);
if (1) {
	$slow = $PGT > 2;
	$backtrace = debug_backtrace();
	$bt = [];
	foreach ($backtrace as $stackLevel) {
		$bt['file'] = preg_replace('|' . addslashes($_SERVER['DOCUMENT_ROOT']) . '|i', '', $stackLevel['file']) . '?' . ($_SERVER['QUERY_STRING'] ?? '');
		$bt['line'] = $stackLevel['line'];
	}
	if ($slow) {
		sendTelegram('sendMessage', ['chat_id' => -799645465, 'text' => ($slow ? '‼' : '') . '️PGT ' . $bt['file'] . ":\n" . $PGT . "\n" . ($_USER['id'] ?? '') . '] ' . ($_USER['lname'] ?? '') . ' ' . ($_USER['fname'] ?? '')]);
	}
}
?>