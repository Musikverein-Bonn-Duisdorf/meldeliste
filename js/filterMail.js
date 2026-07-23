function filterMail() {
    var input, filter, table, tr, i, txtValue;
    input = document.getElementById("filterString");
    if(!input) return;
    filter = input.value.toUpperCase();
    table = document.getElementById("Liste");
    if(!table) return;
    tr = table.getElementsByTagName("div");
    for (i = 0; i < tr.length; i++) {
	if(tr[i].id === "listSentinel") continue;
	if(tr[i].classList && tr[i].classList.contains("mail-list-header")) continue;
	if(tr[i].parentNode !== table) continue;
	if(!tr[i].classList || !tr[i].classList.contains("mail-list-item")) continue;
	txtValue = (typeof listRowSearchText === 'function' ? listRowSearchText(tr[i]) : (tr[i].textContent || tr[i].innerText));
	if (txtValue.toUpperCase().indexOf(filter) > -1) {
	    tr[i].style.display = "";
	    tr[i].classList.remove("list-filtered-out");
	} else {
	    tr[i].style.display = "none";
	    tr[i].classList.add("list-filtered-out");
	}
    }
}
