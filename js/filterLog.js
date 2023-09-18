function filterLog() {
    var input, filter, table, tr, td, i;
    input = document.getElementById("filterString");
    filter = input.value.toUpperCase();
    table = document.getElementById("Liste");
    tr = table.getElementsByTagName("div");
    for (i = 0; i < tr.length; i++) {
	if(tr[i].className=="w3-modal" || tr[i].className=="w3-modal-content") continue;
	if(tr[i].parentNode !== table) continue
	txtValue = tr[i].textContent || td.innerText;
	if (txtValue.toUpperCase().indexOf(filter) > -1) {
	    tr[i].style.display = "";
	} else {
	    tr[i].style.display = "none";
	}
    }
}
