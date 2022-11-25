//based on an Example by @curran
window.requestAnimFrame = (function () {
	return  window.requestAnimationFrame
})();
var canvas = document.querySelector('canvas');
var c = canvas.getContext("2d");

var numStars = 1000;
var radius = '0.' + Math.floor(Math.random() * 9) + 1;
var focalLength = canvas.width * 0.2;
var warp = 0;
var centerX, centerY;

var stars = [], star;
var i;

var animate = true;

initializeStars();

function executeFrame() {

	if (animate)
		requestAnimFrame(executeFrame);
	moveStars();
	drawStars();
	
}

function initializeStars() {
//	c.filter = 'blur(1px)';
	centerX = canvas.width / 2;
	centerY = canvas.height / 2;

	stars = [];
	for (i = 0; i < numStars; i++) {
		star = {
			x: Math.random() * canvas.width,
			y: Math.random() * canvas.height,
			z: Math.random() * canvas.width,
			o: 1,
			h: 180 + Math.floor(Math.random() * 80),
			s: 100,
			l: 60 + Math.floor(Math.random() * 40),
		};
		stars.push(star);
	}
}

function moveStars() {
	for (i = 0; i < numStars; i++) {
		star = stars[i];
		star.z -= 0.1;

		if (star.z <= 0) {
			star.z = canvas.width;
		}
	}
}

function drawStars() {
	var pixelX, pixelY, pixelRadius;

	// Resize to the screen
	if (canvas.width != window.innerWidth || canvas.width != window.innerWidth) {
		canvas.width = window.innerWidth;
		canvas.height = window.innerHeight;
		initializeStars();
	}

	c.fillStyle = "rgba(0,0,10,1)";
	c.fillRect(0, 0, canvas.width, canvas.height);


	stars.sort((a, b) => b.z - a.z);
	let cnt = 0;

	for (let star of stars) {
		cnt++;
		star.h = ((star.z + 1) / canvas.width) * 360;
		star.s = 100;
		star.l = 50;

		pixelX = (star.x - centerX) * (focalLength / star.z);
		pixelX += centerX;
		pixelY = (star.y - centerY) * (focalLength / star.z);
		pixelY += centerY;
		pixelRadius = 5 * (focalLength / star.z);

		c.fillRect(pixelX, pixelY, pixelRadius, pixelRadius);
		c.fillStyle = "hsla(" + star.h + ", " + star.s + "%, " + star.l + "%, " + star.o + ")";

		c.fill();

	}
}

executeFrame();