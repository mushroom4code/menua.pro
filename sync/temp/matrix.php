<?php
$pageTitle = 'Приветсвовать';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';
?>
<script src="https://cdn.jsdelivr.net/npm/vue@2/dist/vue.js"></script>
<style>
	.sq {
		position: relative;
		width: 17px;
		height: 17px;
		margin: 0px;
		cursor: pointer;
		border: 0px solid silver;
		display: inline-block;
		text-align: center;
		font-size: 20px;
		line-height: 70px;
		overflow: hidden;
		/*flex-direction: row;*/
	}
	.vector {
		background-color: white;
		width: 15px;
		height: 15px;
		line-height: 15px;
		border: 1px solid silver;
		display: block;
		text-align: center;
		font-size: 8px;
		overflow: hidden;
		position: absolute;
	}
 

	.v0{
		top:0px;
		left: 0px;
	}
	.v1{
		top:0px;
		left: 50%;
		transform: translateX(-50%);
	}
	.v2{
		top:0px;
		right: 0px;
	}
	.v3{
		top: 50%;
		left:0px;
		transform: translateY(-50%);
	}
	.v4{
		top: 50%;
		right:0px;
		transform: translateY(-50%);
	}

	.v5{
		bottom: 0px;
		left: 0px;
	}
	.v6{
		bottom: 0px;
		left: 50%;
		transform: translateX(-50%);
	}
	.v7{
		bottom: 0px;
		right: 0px;
	}

</style>

<div id="app" style="border: 1px solid silver; display: inline-block;">
	<div  v-for="row in matrix">
		<div class="sq"  v-for="element in row" v-on:click="element.value+=3000;calculateVectors();" :style="{ backgroundColor: `hsla(${360-(Math.min(360,element.value/1))},100%,50%,${Math.min(1,element.value/10)})`}">

	</div>
</div>
<div>{{frame}} ({{Math.round(control)}})</div>
</div>
<div onclick="calculateVectors();" style="cursor: pointer;">calculateVectors();</div>
<div onclick="applyVectors();" style="cursor: pointer;">applyVectors();</div>


<script>
	let rows = 10;
	let columns = 100;
	let viscose = 0.15;
	let corners = '0257';
	var app = new Vue({
	el: '#app',
			data: {
			frame:0,
					control:0,
					message: 'Привет, Vue!',
					matrix: []
			},
			methods: {
			calculateVectors: function(){calculateVectors(); }
			}
	});
	let calculateVectors = () => {
	app.frame++;
	for (let r = 0; r < rows; r++) {
	for (let c = 0; c < columns; c++) {

	let neighbors = [
			app.matrix[r - 1]?.[c - 1],
			app.matrix[r - 1]?.[c],
			app.matrix[r - 1]?.[c + 1],
			app.matrix[r][c - 1],
			app.matrix[r][c + 1],
			app.matrix[r + 1]?.[c - 1],
			app.matrix[r + 1]?.[c],
			app.matrix[r + 1]?.[c + 1]
	];
//	let nbsumm = 0;

	let wills = 0;
	for (let i in neighbors){
	if (typeof (neighbors[i]) !== 'undefined'){
//	nbsumm += neighbors[i].value;
	let will = viscose * (app.matrix[r][c].value - neighbors[i].value) / ((corners.indexOf(i) !== - 1) ?1.4142135623:1);
	wills += will;
	Vue.set(app.matrix[r][c].vectors, i, will > 0?will:0);
	}
	}
	if (wills > 0){

	//wills = 100%
	//each k = will/wills


	for (let m = 0; m < 8; m++){
	if (app.matrix[r][c].vectors[m] > 0){
	let velocity = (wills / app.matrix[r][c].vectors[m]);
	Vue.set(app.matrix[r][c].vectors, m, app.matrix[r][c].vectors[m]);
	}

	}
	}
	}
	}
	setTimeout(applyVectors, 10);
	}

	let applyVectors = () => {
	let control = 0;
	for (let r = 0; r < rows; r++) {
	for (let c = 0; c < columns; c++) {

	let neighbors = [
			app.matrix[r - 1]?.[c - 1],
			app.matrix[r - 1]?.[c],
			app.matrix[r - 1]?.[c + 1],
			app.matrix[r][c - 1],
			app.matrix[r][c + 1],
			app.matrix[r + 1]?.[c - 1],
			app.matrix[r + 1]?.[c],
			app.matrix[r + 1]?.[c + 1]
	];
	for (let i in neighbors){
	if (typeof (neighbors[i]) !== 'undefined'){
	Vue.set(neighbors[i], 'value', neighbors[i].value + app.matrix[r][c].vectors[i]);
	Vue.set(app.matrix[r][c], 'value', app.matrix[r][c].value - app.matrix[r][c].vectors[i]);
	Vue.set(app.matrix[r][c].vectors, i, 0);
	}
	}

	}
	}
	app.control = 0;
	for (let r = 0; r < rows; r++) {
	for (let c = 0; c < columns; c++) {
	app.control += app.matrix[r][c].value;
	}}


//	if (app.frame <= 1500){
	setTimeout(calculateVectors, 10);
//	}

	}




	for (let r = 0; r < rows; r++) {
	for (let c = 0; c < columns; c++) {
	if (!app.matrix[r]) {
	Vue.set(app.matrix, r, []);
	}
	Vue.set(app.matrix[r], c, {value: 0, vectors: [0, 0, 0, 0, 0, 0, 0, 0, 0]});
	}
	}



</script>

