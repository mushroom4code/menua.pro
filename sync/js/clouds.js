/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function Cloud(settings) {
	let N = settings.N;
	this.boody = el('div', {className: 'cloud'});
	let img = new Image();
	img.src = `/css/images/clouds/${N}.png`;

	let that = this;

	this.position = {
		x: Math.random() * 100,
		y: 50 + Math.random() * 20
	};
	this.speed = 20 + 20 * Math.random();


	img.onload = function () {
		that.boody.style.height = img.height + 'px';
		that.boody.style.width = img.width + 'px';
	};



	this.move = function () {
		if (this.position.x > 110) {
			this.position.x = -10;
		}
		if (this.position.x < -20) {
			this.position.x = 110;
		}
		this.position.x -= this.speed / 1000;

		this.boody.style.transform = `translate(${this.position.x}vw,${this.position.y}vh)`;




	};

	this.boody.style.backgroundImage = `url(/css/images/clouds/${N}.png)`;
	qs('#clouds').appendChild(this.boody);


}

let clouds = [];

for (let i = 0; i < 10; i++) {
	clouds.push(new Cloud({N: Math.round(1 + Math.random() * 9)}));
}

function animate() {

	clouds.forEach(cloud => {
		cloud.move();
	});
	requestAnimationFrame(animate);
}
animate();
