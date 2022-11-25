// document.getElementById('submit').onclick = function() {
//     var radios = document.getElementsByName('gift1');
//     for (var radio of radios)
//     {
//     if (radio.checked && radio.value === 'акварелакс') {
//         alert('окок');
//     }
//     }
// }





function prDef(event) {
    event.preventDefault();
}
var button = document.querySelector('.buy');
button.addEventListener('click', prDef);
document.querySelectorAll('.cblabel').forEach((element) => {
    element.addEventListener('click', () => {
        var count = document.querySelectorAll('input:checked').length;
        if (count === 1) {
            document.querySelector('.cblabel').classList.remove('disabled');
            button.removeEventListener('click', prDef);
        }
    })
});