<? include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php'; ?><!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Barcode</title>
        <script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
        <style>
            * {
                margin: 0px;
                padding: 0px;
                line-height: 0.0em;
                box-sizing: border-box;
            }

        </style>
    </head>
    <body>
        <div style="height: 100vh; width: 100vw; display: flex; align-items: center; justify-content: center;">
            <?
            if (!empty($_GET['print'])) {
                $print = json_decode($_GET['print']);
                $employees = mysqlQuery("SELECT * FROM `users` WHERE `idusers` IN (" . implode(", ", $print) . ") AND NOT isnull(`usersBarcode`)");
                while ($employee = mfa($employees)) {
                    ?><svg class="barcode" style="border: 1px solid black; display: inline-block;"
                         jsbarcode-text="<?= $employee['usersLastName']; ?> <?= $employee['usersFirstName']; ?>"
                         jsbarcode-value="<?= $employee['usersBarcode']; ?>"
                         jsbarcode-width="1"
                         jsbarcode-height="30" 
                         jsbarcode-fontSize="12" 
                         jsbarcode-font="Arial" 
                         ></svg><?
                     }
                 }

                 if (
                         isset($_GET['BC']) &&
                         isset($_GET['FN']) &&
                         isset($_GET['LN'])
                 ) {
                     $qty = $_GET['qty'] ?? 1;
                     for ($n = 0; $n < $qty; $n++) {
                         ?><svg class="barcode" style=" display: block;"
                         jsbarcode-text="<?= $_GET['LN']; ?> <?= $_GET['FN']; ?>"
                         jsbarcode-value="<?= $_GET['BC']; ?>"
                         jsbarcode-width="1"
                         jsbarcode-height="30" 
                         jsbarcode-fontSize="12" 
                         jsbarcode-font="Arial" 
                         ></svg><?
                     }
                 }


                 if (isset($_GET['item'])) {

                     $barcodes = mysqlQuery("SELECT * FROM `WH_goods`"
                             . " WHERE `idWH_goods` ='" . FSI($_GET['item']) . "' ");
                     while ($barcode = mfa($barcodes)) {
                         ?>
                    <svg class="barcode" style="border: 1px solid #EEE; display: inline-block;"
                         jsbarcode-text="<?= $barcode['WH_goodsName']; ?>"
                         jsbarcode-value="<?= $barcode['WH_goodBarCode']; ?>"
                         jsbarcode-width="4"
                         jsbarcode-height="240"  
                         jsbarcode-fontSize="12" 
                         jsbarcode-font="Arial" 
                         >
                    </svg>
                    <?
                }
            }

            if (isset($_GET['supplier'])) {

                $barcodes = mysqlQuery("SELECT * FROM `suppliers`"
                        . " WHERE `idsuppliers` ='" . FSI($_GET['supplier']) . "' ");
                while ($barcode = mfa($barcodes)) {
                    ?>
                    <svg class="barcode" style="border: 1px solid black; display: inline-block;"
                         jsbarcode-text="<?= $barcode['suppliersName']; ?>"
                         jsbarcode-value="<?= $barcode['suppliersCode']; ?>"
                         jsbarcode-width="1"
                         jsbarcode-height="30" 
                         jsbarcode-fontSize="12" 
                         jsbarcode-font="Arial" 
                         >
                    </svg>
                    <?
                }
            }


            if (isset($_GET['suppliers'])) {

                $barcodes = mysqlQuery("SELECT * FROM `suppliers`"
                        . " WHERE 1=1 ORDER BY `suppliersName`");
                ?>
                <div style="display: grid; grid-template-columns: 250px auto;">
                    <?
                    while ($barcode = mfa($barcodes)) {
                        if ($barcode['suppliersCode']) {
                            ?>
                            <div style="display: contents;">
                                <div style="vertical-align: middle; line-height: 20px; font-family: calibri; font-size: 20px; padding: 20px; margin: 0px; border-bottom: 1px solid black; ">
                                    <svg class="barcode" style="display: inline; "
                                         jsbarcode-value="<?= $barcode['suppliersCode']; ?>"
                                         jsbarcode-width="1"
                                         jsbarcode-height="30" 
                                         jsbarcode-displayValue="false" 
                                         jsbarcode-margin="0" 
                                         >
                                    </svg>
                                </div>
                                <div style="vertical-align: middle; line-height: 20px; font-family: calibri; font-size: 20px; padding: 20px; margin: 0px; border-bottom: 1px solid black; "><?= $barcode['suppliersName']; ?></div>
                            </div>

                            <?
                        }
                    }
                    ?>
                </div>
                <?
            }
            ?>
            <script>
                JsBarcode(".barcode").init();
            </script>
        </div>
    </body>
</html>
