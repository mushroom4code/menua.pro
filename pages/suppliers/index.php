<?php
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
$pageTitle = rt(['Компании', 'Поставщики', 'Источники сырья', 'Другие компании', 'Коллеги']);
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';


if (R(10)) {
	?>


	<ul class="horisontalMenu">
		<li><a href="?">Все</a></li><li><a href="#" onclick="addSupplierWindow();">Добавить</a></li><li><a href="/sync/plugins/barcodePrint.php?suppliers" target="blank">Печать всех</a></li>
	</ul>
	<div class="divider"></div>
	<?
	if (isset($_GET['supplier'])) {
		$supplier = mfa(mysqlQuery("SELECT * FROM `suppliers` WHERE `idsuppliers` = '" . FSI($_GET['supplier']) . "'"));
		?>
		<script src="/sync/3rdparty/barcode.js" type="text/javascript"></script>
		<div class="box neutral">
			<h2>Данные компании поставщика</h2>
			<div class="box-body">
				<ul class="horisontalMenu">
					<? if (1) { ?><li><a href="#" onclick="GETreloc('page', null); return false;">Основная информация</a></li><? } ?>
					<? if (1) { ?><li><a href="#" onclick="GETreloc('page', 'items'); return false;">Номенклатура</a></li><? } ?>
					<? if (1) { ?><li><a href="#" onclick="GETreloc('page', 'buyings'); return false;">Закупки</a></li><? } ?>
				</ul>

				<? if (empty($_GET['page'])) { ?>

					<div class="personalInfoTable">
						<div style="display: inline-block;">
							<div class="personalUserpic hide"></div>
							<?
							if ($supplier['suppliersCode']) {
								?>
								<a target="_blank" style="padding: 0px;" href="/sync/plugins/barcodePrint.php?supplier=<?= $supplier['idsuppliers']; ?>">
									<svg class="barcode" style="border: 1px solid black; display: block; margin: 0 auto; max-width: 100%; height: auto;"
										 jsbarcode-text="<?= $supplier['suppliersName']; ?>"
										 jsbarcode-value="<?= $supplier['suppliersCode']; ?>"
										 jsbarcode-width="1"
										 jsbarcode-height="30" 
										 jsbarcode-fontSize="12" 
										 jsbarcode-font="Arial" 
										 >
									</svg>
									<script>
										JsBarcode(".barcode").init();
									</script>
								</a>
								<?
							}
							?>

						</div>
						<div>
							<div class="addItemsTable">
								<div>
									<div class="caption">Название:</div>
									<div><a href="#" data-function="editField" data-field="suppliersName" data-key="<?= $supplier['idsuppliers']; ?>" data-value="<?= $supplier['suppliersName']; ?>"><?= trim($supplier['suppliersName']) ? $supplier['suppliersName'] : 'не указан'; ?></a></div>
								</div>

								<div>
									<div class="caption">ИНН:</div>
									<div style="font-size: 0.8em;"><a href="#" data-function="editField" data-field="suppliersINN" data-key="<?= $supplier['idsuppliers']; ?>" data-value="<?= $supplier['suppliersINN']; ?>"><?= trim($supplier['suppliersINN']) ? $supplier['suppliersINN'] : 'Не указан'; ?></a></div>
								</div>

								<div>
									<div class="caption">КПП:</div>
									<div style="font-size: 0.8em;"><?
										$kpps = query2array(mysqlQuery("SELECT * FROM `kpps` WHERE `kppsSupplier`='" . $supplier['idsuppliers'] . "'"));
										foreach ($kpps as $kpp) {
											?>
											<div><?= $kpp['kppsKpp']; ?></div>
											<?
										}
										?>
										<div><a href="#" data-function="editField" data-key="<?= $supplier['idsuppliers']; ?>"  data-field="newKPP" >Добавить КПП</a></div>
									</div>
								</div>

								<div>
									<div class="caption">Номер телефона:</div>
									<div><? if ($supplier['suppliersPhone']) { ?><a href="tel:<?= $supplier['suppliersPhone']; ?>" style="font-size: 2em; vertical-align: middle; display: inline;"><i class="fas fa-phone-square-alt" style="vertical-align: middle;"> </i></a><? } ?> <a href="#" style="vertical-align: middle;" data-function="editField"  data-field="suppliersPhone" data-key="<?= $supplier['idsuppliers']; ?>" data-value="<?= $supplier['suppliersPhone']; ?>"><?= trim($supplier['suppliersPhone']) ? $supplier['suppliersPhone'] : 'не указан'; ?></a></div>
								</div>

								<div>
									<div class="caption">e-mail для заказов:</div>
									<div><? if ($supplier['suppliersEmail']) { ?><a href="mailto:<?= $supplier['suppliersEmail']; ?>" style="font-size: 1.5em; vertical-align: middle; display: inline;"><i class="far fa-envelope" style="vertical-align: middle;"> </i></a><? } ?> <a href="#" style="vertical-align: middle;" data-function="editField"  data-field="suppliersEmail" data-key="<?= $supplier['idsuppliers']; ?>" data-value="<?= $supplier['suppliersEmail']; ?>"><?= trim($supplier['suppliersEmail']) ? $supplier['suppliersEmail'] : 'не указан'; ?></a></div>
								</div>

								<div>
									<div class="caption">Заказ через e-mail:</div>
									<div><input onclick="voidEmail(this.checked,<?= $supplier['idsuppliers']; ?>);" type="checkbox" <?= $supplier['suppliersEmailIsVoid'] ? '' : ' checked'; ?> id="voidEmail"><label for="voidEmail"></label></div>
								</div>



								<div>
									<div class="caption">Штрихкод:</div>
									<div style="font-size: 0.8em;"><a href="#" data-function="editField" data-field="suppliersCode" data-key="<?= $supplier['idsuppliers']; ?>" data-value="<?= $supplier['suppliersCode']; ?>"><?= trim($supplier['suppliersCode']) ? $supplier['suppliersCode'] : 'Не указан'; ?></a></div>
								</div>
								<div>
									<div style="vertical-align: top;" class="caption">Менеджеры:</div>
									<div>
										<?
										$managersRaw = query2array(mysqlQuery("SELECT * "
														. "FROM `suppliersManagers`"
														. "LEFT JOIN `suppliersManagersPhones` ON (`suppliersManagersPhonesManager` = `idsuppliersManagers`)"
														. " WHERE `suppliersManagersSupplier` = '" . $supplier['idsuppliers'] . "'"));
										$managers = [];
										foreach ($managersRaw as $manager) {
											$managers[$manager['idsuppliersManagers']]['id'] = $manager['idsuppliersManagers'];
											$managers[$manager['idsuppliersManagers']]['name'] = $manager['suppliersManagersName'];
											$managers[$manager['idsuppliersManagers']]['phones'][] = [
												'id' => $manager['idsuppliersManagersPhones'],
												'number' => trim($manager['suppliersManagersPhonesPhone']) ? $manager['suppliersManagersPhonesPhone'] : 'Указать номер',
												'comment' => trim($manager['suppliersManagersPhonesComment']) ? $manager['suppliersManagersPhonesComment'] : '----'
											];
										}

										$managers = array_filter_recursive($managers);

										if (count($managers)) {
											foreach ($managers as $manager) {
												?>

												<div class="cherryBoard">
													<div style="padding: 10px;"><?= $manager['name']; ?></div>
													<?
													if (isset($manager['phones']) && count($manager['phones'])) {

														foreach ($manager['phones'] as $phone) {
															if (empty($phone['id'])) {
																continue;
															}
															?>
															<div style="display: grid; grid-template-columns: 2.5em auto; width: 100%; margin: 5px 0px 10px 0px;">

																<div style=" grid-row: span 2; justify-self: center;">
																	<a href="tel: <?= $phone['number']; ?>" style="vertical-align: middle;font-size: 2em; line-height:1em;">
																		<i class="fas fa-phone-square-alt" style="vertical-align: middle;"> </i>
																	</a> 
																</div>
																<div style="line-height: 1em;"><a href="#"  data-function="editField" data-field="managerPhoneNumber" data-key="<?= $phone['id']; ?>" data-value="<?= $phone['number']; ?>"><?= $phone['number']; ?></a></div>
																<div style="line-height: 1em;"> <a href="#"  data-function="editField" data-field="managerPhoneComment" data-key="<?= $phone['id']; ?>" data-value="<?= $phone['comment']; ?>"><?= $phone['comment']; ?></a></div>
															</div>
															<?
														}
														?>
														<div class="C"><a href="#" data-function="editField" data-key="<?= $manager['id']; ?>"  data-field="newPhone" >Добавить телефон</a></div>
														<?
													} else {
														?><br>Нет телефонов<?
													}
													?>
												</div>

												<?
											}
										} else {
											?><?
										}
										?><div class="C"><a href="#" data-function="editField" data-key="<?= $supplier['idsuppliers']; ?>"  data-field="newManager" >Добавить менеджера</a></div>
									</div>
								</div>
								<div>
									<div class="caption" style=" grid-column:1/-1">Дополнительная информация:</div>
									<div style="color: white; grid-column:1/-1; width: 100%;"><div class="console">Когда-нибудь, когда нибудь....<!--<? printr($managers); ?>--></div><span></span></div>

								</div>
								</table>
								<script>
									function voidEmail(state, supplier) {
										fetch('/pages/suppliers/IO.php', {
											body: JSON.stringify({action: 'voidEmail', supplier: supplier, state: !state}),
											credentials: 'include',
											method: 'POST', headers: new Headers({'Content-Type': 'application/json'})
										});
									}
								</script>
							</div>

						</div>

					</div>

				<? } elseif (isset($_GET['page']) && $_GET['page'] == 'items') { ?>


					<ul class="horisontalMenu">
						<li><a href="#" onclick="return false;">равно 0</a></li>
						<li><a href="#" onclick="return false;">меньше min</a></li>
						<li><a href="#" onclick="return false;">меньше &half;</a></li>
						<li><a href="#" onclick="return false;">меньше 100%</a></li>


					</ul>

					<div style="padding: 10px;">
						<?
//					printr($supplier);
						$queryText = "SELECT "
								. "`parents`.`idgoods` AS `idparents`,"
								. "`parents`.`goodsName` AS `parentsName`,"
								. "`items`.`goodsMinAmnt` AS `goodsMinAmnt`,"
								. "`items`.`goodsMaxAmnt` AS `goodsMaxAmnt`,"
								. "`items`.`idgoods` AS `iditems`,"
								. "`items`.`goodsName` AS `itemsName`,
									vatsAmount,
								IFNULL((SELECT 
            MAX(`stocktakingDate`)
        FROM
            `stocktaking`
        WHERE
            `stocktakingItem` = `items`.`idgoods`),'0000-01-01 00:00:00') AS `lastSTdate`,
    (IFNULL((SELECT 
                    `stocktakingQty`
                FROM
                    `stocktaking`
                WHERE
                    `stocktakingItem` = `items`.`idgoods`
                        AND `stocktakingDate` = `lastSTdate`
                LIMIT 1),
            0) + IFNULL((SELECT 
                    SUM(`inQty`)
                FROM
                    `in`
                WHERE
                    `inGoodsId` = `items`.`idgoods`
                        AND `inTime` >= `lastSTdate`
                LIMIT 1),
            0) - IFNULL((SELECT 
                    SUM(`outQty`)
                FROM
                    `out`
                WHERE
                    `outItem` = `items`.`idgoods`
                        AND `outDate` >= `lastSTdate`
						 AND isnull(`outDeleted`)
                LIMIT 1),
            0)) AS `itemsQty`  "
								. " FROM `goods` AS `items` "
								. " LEFT JOIN `goods` AS `parents` ON (`parents`.`idgoods` = `items`.`goodsParent`)"
								. " LEFT JOIN `vats` ON (`vatsGoods` = `items`.`idgoods` AND `vatsSupplier` = '" . $supplier['idsuppliers'] . "')"
								. "  WHERE `items`.`idgoods` IN (SELECT `inGoodsId` FROM `in` WHERE `inSupplier` = '" . $supplier['idsuppliers'] . "' GROUP BY `inGoodsId`)"
								. "";
						//print $queryText;
						$items = query2array(mysqlQuery($queryText));


						uasort($items, function($a, $b) {
							if ($a['itemsQty'] <= 0 && $b['itemsQty'] > 0) {
								return -1;
							} elseif ($a['itemsQty'] > 0 && $b['itemsQty'] <= 0) {
								return 1;
							} elseif ($a['itemsQty'] <= 0 && $b['itemsQty'] <= 0) {
								return mb_strtolower($a['itemsName']) <=> mb_strtolower($b['itemsName']);
							} elseif ($a['itemsQty'] > 0 && $b['itemsQty'] > 0) {

								if ($a['itemsQty'] <= $a['goodsMinAmnt'] && $b['itemsQty'] > $b['goodsMinAmnt']) {
									return -1;
								} elseif ($a['itemsQty'] > $a['goodsMinAmnt'] && $b['itemsQty'] <= $b['goodsMinAmnt']) {
									return 1;
								} else {
									return mb_strtolower($a['itemsName']) <=> mb_strtolower($b['itemsName']);
								}
							}
						});
						?>
						<table>
							<thead>
								<tr>
									<th>#</th>
									<th>БД</th>
									<th><i class="fas fa-barcode"></i></th>
									<th>Категория</th>
									<th>Наименование</th>
									<th>min</th>
									<th>к-во</th>
									<th>max</th>
									<th>НДС</th>
								</tr>
							</thead>
							<tbody id="goodsTable">
								<?
								$n = 0;
								foreach ($items as $item) {
									$n++;
									if ($item['itemsQty'] <= 0) {
										$color = ' style="color: red;"';
									} elseif (isset($item['goodsMinAmnt']) && $item['itemsQty'] <= $item['goodsMinAmnt']) {
										$color = ' style="color: yellow;"';
									} else {
										$color = null;
									}
									?>
									<tr>
										<td class="R"><input type="checkbox" id="item_<?= $item['iditems']; ?>"><label for="item_<?= $item['iditems']; ?>"><?= $n; ?></label></td>
										<td><?= $item['iditems']; ?></td>
										<td>--</td>
										<td><? if ($item['parentsName']) { ?><a href="/pages/goods/?dir=<?= $item['idparents']; ?>" target="_blank"><? } ?><?= $item['parentsName']; ?><? if ($item['parentsName']) { ?></a><? } ?></td>
										<td><a href="/pages/goods/item/?item=<?= $item['iditems']; ?>" target="_blank"><?= $item['itemsName']; ?></a></td>
										<td class="C"><?= $item['goodsMinAmnt']; ?></td>
										<td class="C"<?= $color ?? ''; ?>><?= $item['itemsQty']; ?></td>
										<td class="C"><?= $item['goodsMaxAmnt']; ?></td>
										<td class="C" style="cursor: pointer;"  data-function="editField" data-field="vatsAmount" data-supplier="<?= $supplier['idsuppliers']; ?>"  data-goods="<?= $item['iditems']; ?>"  data-value="<?= $item['vatsAmount']; ?>"><?= $item['vatsAmount'] ?? '-'; ?></td>

									</tr>

									<?
								}
								?>
							</tbody>
						</table>

						<?
//					printr($items);
						?>


					</div>
				<? } elseif (isset($_GET['page']) && $_GET['page'] == 'buyings') { ?>
					<div style="padding: 10px;">Закупочки</div>
				<? } ?>



				<?
			} else {
				?>
				<div class="box neutral">
					<div class="box-body">
						<table style="color: black;" id="suppliers">
							<thead>
								<tr>
									<th style="">№</th>
									<th><i class="fas fa-barcode"></i></th>
									<th>Наименование поставщика</th>
									<th>Тык</th>
								</tr>
							</thead>
							<tbody>

							</tbody>
						</table>
					</div>
				</div>


				<div class="divider"></div>
				<script>
					window.addEventListener('DOMContentLoaded', async function () {
						console.log('DOM Loaded');
						let suppliers = await loadSuppliers();
						renderSuppliersTable(suppliers);
					});
				</script>
				<?
			}//END PAGES
		} else {
			?>
			E403R10
			<?
		}
		?>

		<?
		include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
		