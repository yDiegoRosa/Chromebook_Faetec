document.addEventListener('DOMContentLoaded', function () {

    const monthsBR = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    const tableDays = document.getElementById('dias');

    // Precisam ser iguais a HORARIOS_MANHA / HORARIOS_NOITE do config.php
    const horariosManha = ['7:10', '8:00', '8:50', '9:50', '10:40', '11:30'];
    const horariosNoite = ['18:00', '18:40', '19:20', '20:00', '20:50', '21:30'];

    let dataSelecionada = '';
    let selectedHorarios = [];

    function doisDigitos(n) {
        return n < 10 ? '0' + n : String(n);
    }

    // ---- Calendário ----
    function GetDaysCalendar(mes, ano) {
        document.getElementById('mes').textContent = monthsBR[mes];
        document.getElementById('ano').textContent = ano;

        let firstDayOfWeek = new Date(ano, mes, 1).getDay(); // 0 = domingo (1ª coluna)
        const lastDay = new Date(ano, mes + 1, 0).getDate();
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);

        const dayCells = tableDays.getElementsByTagName('td');

        for (let index = 0; index < dayCells.length; index++) {
            const dia = index - firstDayOfWeek + 1;
            const cell = dayCells[index];

            // Reseta a célula (onclick é substituído, então não acumula cliques de meses anteriores)
            cell.className = '';
            cell.textContent = '';
            cell.onclick = null;
            cell.removeAttribute('title');

            if (dia < 1 || dia > lastDay) continue;

            const dt = new Date(ano, mes, dia);
            const valor = `${doisDigitos(dia)}-${doisDigitos(mes + 1)}-${ano}`;
            cell.textContent = dia;

            if (dt.getTime() === hoje.getTime()) cell.classList.add('dia-atual');
            if (valor === dataSelecionada) cell.classList.add('dia-selecionado');

            const fimDeSemana = dt.getDay() === 0 || dt.getDay() === 6;
            if (dt < hoje || fimDeSemana) {
                // Dias passados e fins de semana não podem ser escolhidos
                cell.classList.add('dia-indisponivel');
                cell.title = fimDeSemana ? 'Sem aulas no fim de semana' : 'Data já passou';
                continue;
            }

            cell.onclick = function () {
                const selected = tableDays.querySelector('.dia-selecionado');
                if (selected) selected.classList.remove('dia-selecionado');
                cell.classList.add('dia-selecionado');
                dataSelecionada = valor;
            };
        }
    }

    const now = new Date();
    let mes = now.getMonth();
    let ano = now.getFullYear();
    GetDaysCalendar(mes, ano);

    document.getElementById('btn_next').addEventListener('click', function () {
        mes++;
        if (mes > 11) { mes = 0; ano++; }
        GetDaysCalendar(mes, ano);
    });

    document.getElementById('btn_prev').addEventListener('click', function () {
        // Não volta para meses que já passaram
        if (ano === now.getFullYear() && mes === now.getMonth()) return;
        mes--;
        if (mes < 0) { mes = 11; ano--; }
        GetDaysCalendar(mes, ano);
    });

    // ---- Turno e horários ----
    function makeSelectable() {
        const pElements = document.getElementById('turno').getElementsByTagName('p');
        for (let i = 0; i < pElements.length; i++) {
            pElements[i].className = 'turno';
            pElements[i].addEventListener('click', function () {
                for (let j = 0; j < pElements.length; j++) pElements[j].classList.remove('selected');
                this.classList.add('selected');
                mudarHorarios(this.id);
            });
        }
        pElements[0].classList.add('selected');
    }

    function mudarHorarios(turno) {
        selectedHorarios = [];
        const horarios = turno === 'noite' ? horariosNoite : horariosManha;
        const horas = document.querySelector('.horas');
        horas.replaceChildren();

        horarios.forEach(function (horario) {
            const div = document.createElement('div');
            div.textContent = horario;
            div.className = 'horario';
            div.addEventListener('click', function () {
                if (div.classList.toggle('selected')) {
                    selectedHorarios.push(horario);
                } else {
                    selectedHorarios = selectedHorarios.filter(h => h !== horario);
                }
            });
            horas.appendChild(div);
        });
    }

    makeSelectable();
    mudarHorarios('manha');

    // ---- Envio ----
    document.getElementById('submit').addEventListener('click', function (ev) {
        ev.preventDefault();

        if (!dataSelecionada) { alert('Selecione um dia no calendário.'); return; }
        if (selectedHorarios.length === 0) { alert('Selecione pelo menos um horário.'); return; }

        const body = new URLSearchParams({
            csrf: document.getElementById('csrf').value,
            data: dataSelecionada,
            horarios: selectedHorarios.join(','),
            preferencia: document.getElementById('preferencia').value
        });

        const botao = this;
        botao.disabled = true;

        fetch('createAgendamento.php', { method: 'POST', body: body })
            .then(resp => resp.text().then(texto => ({ ok: resp.ok, texto })))
            .then(({ ok, texto }) => {
                alert(texto); // alert mostra texto puro, não interpreta HTML
                if (ok) window.location.href = 'AgendaSemanal.php?data=' + encodeURIComponent(dataSelecionada);
            })
            .catch(() => alert('Não foi possível conectar ao servidor. Tente novamente.'))
            .finally(() => { botao.disabled = false; });
    });
});
