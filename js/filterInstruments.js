function filterMusiker() {
    var input = document.getElementById("filterString");
    var filter = input ? String(input.value || "").toUpperCase() : "";
    var chip = document.getElementById("filterInsured");
    var insuredOnly = !!(chip && chip.classList.contains("is-active"));
    var table = document.getElementById("Liste");
    if (!table) return;

    var rows = [];
    var children = table.children;
    var i;
    for (i = 0; i < children.length; i++) {
        var el = children[i];
        if (!el || el.nodeType !== 1) continue;
        if (el.id === "listSentinel") continue;
        if (el.classList && (el.classList.contains("list-row") || el.classList.contains("inv-row"))) {
            rows.push(el);
        }
    }

    for (i = 0; i < rows.length; i++) {
        var row = rows[i];
        var txtValue = (typeof listRowSearchText === "function"
            ? listRowSearchText(row)
            : (row.textContent || row.innerText || ""));
        var textOk = filter === "" || txtValue.toUpperCase().indexOf(filter) > -1;
        var insuredOk = !insuredOnly || row.getAttribute("data-insured") === "1";
        if (textOk && insuredOk) {
            row.style.display = "";
            row.classList.remove("list-filtered-out");
        } else {
            row.style.display = "none";
            row.classList.add("list-filtered-out");
        }
    }
}

function setInsuredFilter(on, updateUrl) {
    var chip = document.getElementById("filterInsured");
    if (!chip) return;
    on = !!on;
    chip.classList.toggle("is-active", on);
    chip.setAttribute("aria-pressed", on ? "true" : "false");
    if (updateUrl !== false && window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        if (on) {
            url.searchParams.set("versichert", "1");
        } else {
            url.searchParams.delete("versichert");
        }
        window.history.replaceState({}, "", url.pathname + url.search + url.hash);
    }
    filterMusiker();
}

function toggleInsuredFilter() {
    var chip = document.getElementById("filterInsured");
    if (!chip) return;
    setInsuredFilter(!chip.classList.contains("is-active"), true);
}

function initInsuredFilterFromQuery() {
    var chip = document.getElementById("filterInsured");
    if (!chip) return;
    var params = new URLSearchParams(window.location.search);
    var on = params.get("versichert") === "1" || chip.classList.contains("is-active");
    setInsuredFilter(on, false);
}
