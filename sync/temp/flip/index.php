<?php

$pageTitle = 'Финансы';
include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/setup.php';

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/top.php';
?>
<style>

	.card-wrapper {
		display: inline-block;
		perspective: 400px;
	}

	.card-wrapper .card {
		position: relative;
		cursor: pointer;
		transition-duration: 0.3s;
		transition-timing-function: ease-out;
		transform-style: preserve-3d;
	}

	.card-wrapper .card .front,
	.card-wrapper .card .back {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		backface-visibility: hidden;
		transform: rotateX(0deg);
		animation: none;
	}
	.card-wrapper .card .front {
		z-index: 2;
	}

	.card-wrapper.flip-left .card .back {
		transform: rotateY(-180deg);
	}
	.card-wrapper.flip-left.card--flipped .card {
		transform: rotateY(-180deg);
	}
	.card-wrapper,
	.card {
		width: 200px;
		height: 200px;
		margin: 10px;
	}
	.blured {

	}
	.blured::after{
		background-color: hsla(0,0%,100%,0.9);
		content: '...loading...';
		position: absolute;
		width: 100%;
		height: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		color: darkgray;
	}
	.card .front,
	.card .back {
		display: flex;
		align-items: center;
		justify-content: center;
		border: 1px solid #0aa;
		border-radius: 10px;
		font-size: 2rem;
		overflow: hidden;
	}
	.card .front {
		color: #0aa;
		background: #FFFFFF;
		font-weight: 700;


	}
	.card .back {
		font-size: 1.5rem;
		color: #FFFFFF;
		background: #0aa;
	}




</style>
<div class="box neutral">
	<div class="box-body">

		<div class="card-wrapper flip-left" onclick="flipMe(this);">
			<div class="card">
				<div class="front">1</div>
				<div class="back">BACK</div>
			</div>
		</div>
		<div class="card-wrapper flip-left" onclick="flipMe(this);">
			<div class="card">
				<div class="front">2</div>
				<div class="back">BACK</div>
			</div>
		</div>
		<div class="card-wrapper flip-left" onclick="flipMe(this);">
			<div class="card">
				<div class="front">3</div>
				<div class="back">BACK</div>
			</div>
		</div>
		<div class="card-wrapper flip-left" onclick="flipMe(this);">
			<div class="card">
				<div class="front">4</div>
				<div class="back">BACK</div>
			</div>
		</div>

		<div class="card-wrapper flip-left" onclick="flipMe(this);">
			<div class="card">
				<div class="front">5</div>
				<div class="back">BACK</div>
			</div>
		</div>



	</div>
</div>

<script>
	async function flipMe(obj) {

		await fillContent(obj);
		if (obj.classList.contains('card--flipped')) {
			obj.classList.add('card--unflip');
			obj.classList.remove('card--flipped', 'card--unflip');

		} else {
			obj.classList.add("card--flipped");
		}
	}

	function resetAnimation(el) {
		el.style.animation = 'none';
		el.offsetHeight; /* trigger reflow */
		el.style.animation = null;
	}

	async function fillContent(obj, content = '') {
		let promise = new Promise((resolve, reject) => {
			setTimeout(() => resolve("готово!"), 1000)
		});
		let canvas;
		if (obj.classList.contains('card--flipped')) {
			canvas = qs('.front', obj);
			resetAnimation(qs('.back', obj));
			qs('.back', obj).classList.add('blured');

		} else {
			resetAnimation(qs('.front', obj));
			canvas = qs('.back', obj);
			qs('.front', obj).classList.add('blured');
		}
		canvas.innerHTML = RDS(2, 1);
		canvas.classList.remove('blured');
		return promise;
	}

</script>

<?

include $_SERVER['DOCUMENT_ROOT'] . '/sync/includes/html/bottom.php';
