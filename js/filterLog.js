function filterLog() {
    var input, filter, table, tr, i, txtValue;
    input = document.getElementById("filterString");
    filter = input.value.toUpperCase();
    table = document.getElementById("Liste");
    tr = table.getElementsByTagName("div");
    for (i = 0; i < tr.length; i++) {
	if(tr[i].id === "listSentinel") continue;
	if(tr[i].className=="w3-modal" || tr[i].className=="w3-modal-content") continue;
	if(tr[i].parentNode !== table) continue;
	txtValue = tr[i].textContent || tr[i].innerText;
	if (txtValue.toUpperCase().indexOf(filter) > -1) {
	    tr[i].style.display = "";
	    tr[i].classList.remove("list-filtered-out");
	} else {
	    tr[i].style.display = "none";
	    tr[i].classList.add("list-filtered-out");
	}
    }
}
