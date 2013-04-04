window.onload = function() {
    var selects = document.getElementsByTagName("select");
    for (i in selects) {
        if (selects[i].name != "site")
            continue;

        selects[i].onchange = function() {
            var formId = this.form.id.substring(4);
            var params = "site=" + this.options[this.selectedIndex].value + "&catsort=" + catsort;
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.open("GET", "./ajax.php?" + params, true);
            xmlhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200)
                    document.forms["form" + formId].category.innerHTML = this.responseText;
            };
            xmlhttp.send(null);
        };
    }
}
