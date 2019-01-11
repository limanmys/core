@extends('layouts.app')

@section('content')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.3/Chart.min.js"></script>
    <div class="charts">
        <div class="card float-left chart">
            Sistemdeki Serverların İşletim Sistemleri
            <canvas id="operating_system"></canvas>
        </div>
        <div class="card float-left chart">
            Talepler
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Servis Sayilari
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>
        <div class="card float-left chart">
            Bos
            <canvas id="requests"></canvas>
        </div>

    </div>

    <script>
        let ctx = document.getElementById("operating_system").getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ["Linux", "Linux SSH", "Windows", "Windows Powershell"],
                datasets: [{
                    data: [{{$linux_count}}, {{$linux_ssh_count}}, {{$windows_count}}, {{$windows_powershell_count}}],
                    backgroundColor: [
                        'rgba(255, 99, 132)',
                        'rgba(255, 33, 80)',
                        'rgba(54, 162, 235)',
                        'rgba(123, 191, 237)',
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });
        let ctx2 = document.getElementById("requests").getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ["Sunucu", "Eklenti", "Betik" ,"Diğer"],
                datasets: [{
                    data: [63, 5,20,30],
                    backgroundColor: [
                        'rgba(255, 99, 132)',
                        'rgba(54, 162, 235)',
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero:true
                        }
                    }]
                }
            }
        });
    </script>
@endsection