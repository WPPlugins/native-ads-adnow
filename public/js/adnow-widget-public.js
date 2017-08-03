window.onload = function() {
    var element=document.getElementsByClassName('header_fix_top');
    if(element[0]){
		document.body.style.marginTop = '80px';
		window.scrollBy(0, -100);
    }
};