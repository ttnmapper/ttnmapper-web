$(document).ready( function () {
    findExperiments(findGetParameter("experiment"))
} );

// document.getElementById("search").onsubmit = function() {
//     findExperiments(document.getElementById("experiment-name").value);
// }

function findExperiments(experiment) {

    $("#dataTable:not(:first)").remove(); // clear all rows

    if(experiment === "") {
        var loader = document.getElementById('experiments-loading');
        loader.style.display = 'none';
        return;
    }

    document.getElementById('experiment-name').value = experiment;

    var tbodyRef = document.getElementById('dataTable').getElementsByTagName('tbody')[0];

    // var url = new URL('http://localhost:8080/experiment/find')
    var url = new URL('https://api.ttnmapper.org/experiment/find')
    var params = {
        experiment: experiment,
    };

    url.search = new URLSearchParams(params).toString();
    fetch(url)
        .then(response => response.json())
        .then(data => {
            for(experiment of data) {
                // table.row.add( [
                //     experiment.id,
                //     experiment.name,
                //     "click"
                // ] ).draw( false );
                console.log(experiment.id, experiment.name);

                var newRow = tbodyRef.insertRow();

                var cellOne = newRow.insertCell();
                var cellTwo = newRow.insertCell();
                var cellThree = newRow.insertCell();

                cellOne.innerText = experiment.name;

                cellTwo.innerHTML = `
                  <form target="_blank">
                    <input type="hidden" name="experiment" value="${he.encode(experiment.name)}">
                    <a href="/experiments/?experiment=${he.encode(experiment.name)}">
                      <button type="submit" class="btn btn-primary" formaction="/experiments/">View Map</button>
                    </a>
                  </form>
                `;

                cellThree.innerHTML = `
                  <form target="_blank">
                    <input type="hidden" name="experiment" value="${he.encode(experiment.name)}">
                    <a href="/experiments/csv.php?experiment=${he.encode(experiment.name)}">
                      <button type="submit" class="btn btn-secondary" formaction="/experiments/csv.php">CSV data</button>
                    </a>
                  </form>
                `;

            }
        }).then(() => {
            // $('#dataTable').DataTable();

            var loader = document.getElementById('experiments-loading');
            loader.style.display = 'none';
        }
    );
}