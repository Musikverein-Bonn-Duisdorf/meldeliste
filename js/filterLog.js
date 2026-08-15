function filterLog() {
    var input, filter, table, i, row, txtValue;
    input = document.getElementById("filterString");
    filter = input ? input.value : '';
    table = document.getElementById("Liste");
    // Only direct list rows (not nested log-time / log-message divs) — O(rows), not O(descendants)
    for (i = 0; i < table.children.length; i++) {
	row = table.children[i];
	if(row.nodeType !== 1) continue;
	if(row.id === "listSentinel") continue;
	if(row.className=="w3-modal" || row.className=="w3-modal-content") continue;
	txtValue = (typeof listRowSearchText === 'function' ? listRowSearchText(row) : (row.textContent || row.innerText));
	if (typeof listRowMatchesQuery === 'function' ? listRowMatchesQuery(txtValue, filter) : String(txtValue).toUpperCase().indexOf(String(filter).toUpperCase()) > -1) {
	    row.style.display = "";
	    row.classList.remove("list-filtered-out");
	} else {
	    row.style.display = "none";
	    row.classList.add("list-filtered-out");
	}
    }
}
