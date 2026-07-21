function filterTermine() {
    var input, filter, table, tr, i, txtValue, dataSearch;
    input = document.getElementById("filterString");
    if(!input) return;
    filter = input.value.toUpperCase();
    table = document.getElementById("Liste");
    if(!table) return;
    tr = table.children;
    for (i = 0; i < tr.length; i++) {
        if(tr[i].id === "listSentinel") continue;
        if(tr[i].tagName !== "DIV") continue;
        if(tr[i].className === "w3-modal" || tr[i].className === "w3-modal-content") continue;
        txtValue = (typeof listRowSearchText === 'function'
            ? listRowSearchText(tr[i])
            : ((dataSearch = tr[i].getAttribute("data-search")) || tr[i].textContent || tr[i].innerText || ""));
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
            tr[i].classList.remove("list-filtered-out");
        } else {
            tr[i].style.display = "none";
            tr[i].classList.add("list-filtered-out");
        }
    }
}
