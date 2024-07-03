const images = document.querySelectorAll(".img-container img");
const input = document.getElementById("input");

images.forEach((img) => {
	let url = img.getAttribute("src");
	const regex = /\/(\d+)\/(\d+)$/;
	const matches = url.match(regex);
	let x;
	let y;
	if (matches) {
		x = matches[1];
		y = matches[2];
	}

	img.style.width = x + "px";
	img.style.height = y + "px";
});

input.addEventListener("click", (e) => {
	e.target.select();
});
