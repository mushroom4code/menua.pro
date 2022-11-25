<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?>
<? include 'includes/top.php'; ?>


<div style="padding: 10px;">Ключ-карта <input id="cardKey" style="display: inline-block; width: auto;" type="text" value="<?= $employee['usersCard']; ?>"> <input onclick="saveCard();" type="button" value="сохранить"></div>

<object id="floorplan" type="image/svg+xml" data="/css/images/skud/index.svg?<?= RDS(); ?>"></object>

<script>
    function saveCard() {
        fetch('personal_IO.php', {
            body: JSON.stringify({
                action: 'save_SKUD_card',
                user: <?= $employee['idusers']; ?>,
                card: qs('#cardKey').value
            }),
            credentials: 'include',
            method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
        });
    }

    let a = document.getElementById("floorplan");
    a.addEventListener("load", function () {
        let svgDoc = a.contentDocument;
        for (let skudElement of svgDoc.querySelectorAll('[data-skud]')) {
            skudElement.addEventListener('click', function () {
                fetch('personal_IO.php', {
                    body: JSON.stringify({
                        action: 'toggle_SKUD',
                        user: <?= $employee['idusers']; ?>,
                        lock: skudElement.dataset.skud
                    }),
                    credentials: 'include',
                    method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
                }).then(result => result.text()).then(async function (text) {
                    try {
                        let jsn = JSON.parse(text);
                        if (jsn.allow) {
                            skudElement.style.fill = 'green';
                        } else {
                            skudElement.style.fill = 'red';
                        }
                    } catch (e) {

                    }
                });
            });

            fetch('personal_IO.php', {
                body: JSON.stringify({
                    action: 'check_SKUD',
                    user: <?= $employee['idusers']; ?>,
                    lock: skudElement.dataset.skud
                }),
                credentials: 'include',
                method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
            }).then(result => result.text()).then(async function (text) {
                try {
                    let jsn = JSON.parse(text);
                    if (jsn.allow) {
                        skudElement.style.fill = 'green';
                    } else {
                        skudElement.style.fill = 'red';
                    }
                } catch (e) {

                }
            });
        }

    }, false);




</script>




<? include 'includes/bottom.php'; ?>