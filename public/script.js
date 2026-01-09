fetch('/upload-data')
  .then(res => res.json())
  .then(data => {
    // Poblar la tabla
    const tableBody = document.querySelector('#dataTable tbody');
    data.forEach(row => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${row.razonSocial}</td>
        <td>${row.saldo}</td>
        <td>${row.fechaMin}</td>
        <td>${row.vencimiento}</td>
        <td>${row.intereses}</td>
        <td>${row.moratorios}</td>
        <td>${row.capitalPagado}</td>
      `;
      tableBody.appendChild(tr);
    });

    // Crear gráfico
    const ctx = document.getElementById('balanceChart').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: data.map(row => row.razonSocial),
        datasets: [{
          label: 'Saldos',
          data: data.map(row => row.saldo),
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
        }],
      },
    });
  });
