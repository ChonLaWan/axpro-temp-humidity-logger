<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กราฟอุณหภูมิและความชื้น (AX PRO)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #e9ecef;
            color: #343a40;
            font-size: 1rem;
        }
        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .page-title {
            font-size: 2rem;
            font-weight: 600;
            color: #495057;
        }
        .info-row {
            margin-top: 2rem;
        }
        .info-box {
            font-size: 1.1rem;
            padding: 10px 0;
            border-radius: 8px;
        }
        .info-box span {
            font-weight: bold;
        }
        .form-row .form-control,
        .form-row .form-select {
            height: 45px;
            font-size: 1rem;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            font-weight: 600;
        }
        .chart-container {
            position: relative;
            height: 550px;
            margin-top: 20px;
        }
        .refresh-status {
            text-align: center;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.2.1/dist/chartjs-plugin-annotation.min.js"></script>
</head>
<body>

<div class="container">
    <h1 class="text-center mb-4 page-title">กราฟอุณหภูมิและความชื้น (AX PRO)</h1>

    <div class="row align-items-end g-3 mb-4">
        <div class="col-md-3">
            <label for="startDate" class="form-label">เลือกวันที่</label>
            <input type="date" class="form-control" id="startDate">
        </div>
        <div class="col-md-3">
            <label for="startTime" class="form-label">เวลา [HH:MM]</label>
            <input type="time" class="form-control" id="startTime">
        </div>
        <div class="col-md-3">
            <label for="duration" class="form-label">ย้อนหลัง</label>
            <select class="form-select" id="duration">
                <option value="30" selected>30 นาที</option>
                <option value="60">1 ชั่วโมง</option>
                <option value="180">3 ชั่วโมง</option>
                <option value="360">6 ชั่วโมง</option>
                <option value="720">12 ชั่วโมง</option>
                <option value="1440">24 ชั่วโมง</option>
            </select>
        </div>
        <div class="col-md-3">
            <button id="loadDataBtn" class="btn btn-primary w-100">โหลดข้อมูล</button>
        </div>
    </div>

    <div class="row text-center info-row">
        <div class="col-md-6">
            <p class="info-box">อุณหภูมิที่เหมาะสม: <span class="text-danger">20°C - 25°C</span></p>
        </div>
        <div class="col-md-6">
            <p class="info-box">ความชื้นที่เหมาะสม: <span class="text-primary">40% - 55%</span></p>
        </div>
    </div>
    <div id="refreshStatus" class="refresh-status">กำลังรีเฟรชข้อมูลอัตโนมัติ...</div>

    <div class="chart-container">
        <canvas id="sensorChart"></canvas>
    </div>
</div>

<script>
    let myChart = null;
    let refreshIntervalId = null;
    let maxDataPoints = 60; // จำนวนจุดข้อมูลที่แสดงสูงสุด

    // ฟังก์ชันสำหรับสร้างหรืออัปเดตกราฟ
    function updateChart(labels, temp, humid) {
        if (!myChart) {
            const ctx = document.getElementById('sensorChart').getContext('2d');
            myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'อุณหภูมิ (°C)', data: temp, borderColor: 'rgb(255, 99, 132)', yAxisID: 'y' },
                        { label: 'ความชื้น (%RH)', data: humid, borderColor: 'rgb(54, 162, 235)', yAxisID: 'y1' }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 0 },
                    scales: {
                        y: { type: 'linear', display: true, position: 'left', title: { display: true, text: 'อุณหภูมิ (°C)', font: { size: 14 } }, min: 0 },
                        y1: { type: 'linear', display: true, position: 'right', title: { display: true, text: 'ความชื้น (%RH)', font: { size: 14 } }, grid: { drawOnChartArea: false }, min: 0, max: 100 }
                    },
                    plugins: {
                        annotation: {
                            annotations: {
                                idealTempRange: { type: 'box', yMin: 20, yMax: 25, backgroundColor: 'rgba(255, 99, 132, 0.15)', borderColor: 'transparent', label: { content: 'Ideal Temp', enabled: true, position: 'start', backgroundColor: 'transparent', color: '#333' } },
                                idealHumRange: { type: 'box', yMin: 40, yMax: 55, backgroundColor: 'rgba(54, 162, 235, 0.15)', borderColor: 'transparent', yScaleID: 'y1', label: { content: 'Ideal RH', enabled: true, position: 'start', backgroundColor: 'transparent', color: '#333' } }
                            }
                        }
                    }
                }
            });
        } else {
            myChart.data.labels = labels;
            myChart.data.datasets[0].data = temp;
            myChart.data.datasets[1].data = humid;
            myChart.update();
        }
    }

    // ฟังก์ชันสำหรับเพิ่มข้อมูลใหม่และลบข้อมูลเก่า (ให้กราฟวิ่ง)
    function addData(data) {
        if (!myChart) return;
        
        myChart.data.labels.push(data.label);
        myChart.data.datasets[0].data.push(data.temperature);
        myChart.data.datasets[1].data.push(data.humidity);

        if (myChart.data.labels.length > maxDataPoints) {
            myChart.data.labels.shift();
            myChart.data.datasets[0].data.shift();
            myChart.data.datasets[1].data.shift();
        }
        myChart.update();
    }

    // ฟังก์ชันสำหรับดึงข้อมูลล่าสุด
    function getLatestData() {
        const fetchUrl = `get_data.php?latest=true`;
        fetch(fetchUrl)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    const latest = data[0];
                    addData({
                        label: latest.recorded_at,
                        temperature: parseFloat(latest.temperature),
                        humidity: parseFloat(latest.humidity)
                    });
                }
            });
    }

    // ฟังก์ชันสำหรับโหลดข้อมูลเริ่มต้นเมื่อกดปุ่ม
    function loadInitialData() {
        const startDate = document.getElementById('startDate').value;
        const startTime = document.getElementById('startTime').value;
        const duration = document.getElementById('duration').value;
        
        const fetchUrl = `get_data.php?date=${startDate}&time=${startTime}&duration=${duration}`;

        fetch(fetchUrl)
            .then(response => response.json())
            .then(sensorData => {
                if (sensorData.error || sensorData.length === 0) {
                    console.error('Error or no data fetched:', sensorData.error);
                    updateChart([], [], []);
                    return;
                }
                const labels = sensorData.map(row => row.recorded_at);
                const temperatureData = sensorData.map(row => parseFloat(row.temperature));
                const humidityData = sensorData.map(row => parseFloat(row.humidity));
                updateChart(labels, temperatureData, humidityData);
            });
    }

    // ฟังก์ชันสำหรับกำหนดค่าเริ่มต้นของวันที่และเวลา
    function setInitialDateTime() {
        const now = new Date();
        const dateStr = now.toISOString().substring(0, 10);
        const timeStr = now.toTimeString().substring(0, 5);
        document.getElementById('startDate').value = dateStr;
        document.getElementById('startTime').value = timeStr;
    }

    // ฟังก์ชันสำหรับเริ่มต้นการรีเฟรชข้อมูล
    function startAutoRefresh() {
        if (refreshIntervalId) {
            clearInterval(refreshIntervalId);
        }
        refreshIntervalId = setInterval(getLatestData, 5000);
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        setInitialDateTime();
        loadInitialData();
        startAutoRefresh();
        
        document.getElementById('loadDataBtn').addEventListener('click', function() {
            loadInitialData();
            startAutoRefresh();
        });
    });
</script>

</body>
</html>
