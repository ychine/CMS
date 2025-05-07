<?php
session_start();

// Display toast if a success message exists
if (isset($_SESSION['joined_faculty_success'])): ?>
    <script>
        window.onload = function() {
            showToast("<?php echo addslashes($_SESSION['joined_faculty_success']); ?>", "success");
        };
    </script>
    <?php unset($_SESSION['joined_faculty_success']); ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard FM</title>
    <link href="../../src/tailwind/output.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Onest:wght@400;500;600;700&family=Overpass:wght@400;500;600;700&family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-overpass { font-family: 'Overpass', sans-serif; }
        .font-onest { font-family: 'Onest', sans-serif; }

        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            color: #fff;
            border-radius: 10px;
            font-family: 'Onest', sans-serif;
            font-size: 14px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            z-index: 1000;
            animation: fadein 0.5s, fadeout 0.5s 3s;
        }

        .toast.success {
            background-color: #28a745;
        }

        .toast.error {
            background-color: #dc3545;
        }

        .toast.success {
            background-color: #4CAF50; /* Green */
            color: white;
        }

        @keyframes fadein {
            from { opacity: 0; bottom: 20px; }
            to { opacity: 1; bottom: 30px; }
        }

        @keyframes fadeout {
            from { opacity: 1; bottom: 30px; }
            to { opacity: 0; bottom: 20px; }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <div class="flex-1 flex flex-col px-[50px] pt-[15px] pb-[30px] overflow-y-auto">
        <h1 class="py-[10px] text-[35px] font-overpass font-bold">Dashboard DN</h1>
        
        <!-- Stats Overview Row -->
        <div class="grid grid-cols-4 gap-4 mb-5">
            <div class="bg-white p-[20px] rounded-lg shadow-sm border border-gray-100">
                <div class="text-sm text-gray-500 mb-1">Total Members</div>
                <div class="text-2xl font-bold">152</div>
                <div class="text-xs text-green-500 mt-1">+12 this month</div>
            </div>
            <div class="bg-white p-[20px] rounded-lg shadow-sm border border-gray-100">
                <div class="text-sm text-gray-500 mb-1">Active Faculty</div>
                <div class="text-2xl font-bold">87</div>
                <div class="text-xs text-green-500 mt-1">94% participation</div>
            </div>
            <div class="bg-white p-[20px] rounded-lg shadow-sm border border-gray-100">
                <div class="text-sm text-gray-500 mb-1">Tasks Created</div>
                <div class="text-2xl font-bold">45</div>
                <div class="text-xs text-blue-500 mt-1">This semester</div>
            </div>
            <div class="bg-white p-[20px] rounded-lg shadow-sm border border-gray-100">
                <div class="text-sm text-gray-500 mb-1">Completion Rate</div>
                <div class="text-2xl font-bold">78%</div>
                <div class="text-xs text-amber-500 mt-1">+5% from last period</div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="grid grid-cols-2 gap-5">
            <!-- Submissions Panel -->
            <div class="bg-white p-[30px] font-overpass rounded-lg shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold">Submissions</h2>
                    <div class="text-sm text-blue-600">On-Going Task: 24-25 COURSE SYLLABUS</div>
                </div>
                
                <div class="flex space-x-4 mb-5">
                    <a href="submissionspage.php?type=pending" class="flex-1">
                        <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold mr-3">7</div>
                                <div class="text-sm">Pending Review</div>
                                <div class="ml-auto">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-gray-300 h-1 mt-2">
                                <div class="bg-yellow-500 h-1" style="width: 50%"></div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="submissionspage.php?type=unaccomplished" class="flex-1">
                        <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold mr-3">3</div>
                                <div class="text-sm">Unaccomplished</div>
                                <div class="ml-auto">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-gray-300 h-1 mt-2">
                                <div class="bg-red-500 h-1" style="width: 30%"></div>
                            </div>
                        </div>
                    </a>
                    
                    <a href="submissionspage.php?type=complete" class="flex-1">
                        <div class="bg-gray-100 border rounded-lg p-3 hover:bg-gray-200 transition-all duration-200 cursor-pointer">
                            <div class="flex items-center">
                                <div class="text-2xl font-bold mr-3">10</div>
                                <div class="text-sm">Complete</div>
                                <div class="ml-auto">
                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="w-full bg-gray-300 h-1 mt-2">
                                <div class="bg-green-500 h-1" style="width: 100%"></div>
                            </div>
                        </div>
                    </a>
                </div>
                
                <div class="flex items-center">
                    <div class="text-xs mr-2 font-medium">50%</div>
                    <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
                        <div class="bg-green-500 h-2" style="width: 50%"></div>
                    </div>
                    <div class="ml-2">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Faculty Members Panel -->
            <div class="bg-white p-[30px] rounded-lg shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold font-overpass">Faculty Members</h2>
                    <a href="faculty-management.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
                
                <!-- Faculty Stats -->
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
                        <div class="text-blue-700 font-medium text-xl">87</div>
                        <div class="text-sm text-blue-600">Active</div>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-100">
                        <div class="text-yellow-700 font-medium text-xl">23</div>
                        <div class="text-sm text-yellow-600">On Leave</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                        <div class="text-green-700 font-medium text-xl">42</div>
                        <div class="text-sm text-green-600">New This Year</div>
                    </div>
                </div>
                
                <!-- Faculty Distribution Chart -->
                <div class="mb-4">
                    <canvas id="facultyDeptChart" height="180"></canvas>
                </div>
                
                <!-- Recent Faculty -->
                <div class="text-sm text-gray-500 mt-4">Recently Added</div>
                <div class="faculty-grid mt-2">
                    <div class="flex items-center p-2 hover:bg-gray-50 rounded">
                        <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-700 font-medium mr-3">JD</div>
                        <div>
                            <div class="font-medium text-sm">Randy Otero</div>
                            <div class="text-xs text-gray-500">Networking</div>
                        </div>
                    </div>
                    <div class="flex items-center p-2 hover:bg-gray-50 rounded">
                        <div class="w-8 h-8 bg-blue-200 rounded-full flex items-center justify-center text-blue-700 font-medium mr-3">AS</div>
                        <div>
                            <div class="font-medium text-sm">Racquel Cortez</div>
                            <div class="text-xs text-gray-500">Application Development</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pending Reviews Panel -->
            <div class="bg-white p-[30px] rounded-lg shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold font-overpass">Pending Reviews</h2>
                    <a href="reviews.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                </div>
                
                <!-- Reviews Chart -->
                <div class="mb-6">
                    <canvas id="reviewsChart" height="200"></canvas>
                </div>
                
                <!-- Recent Reviews -->
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div class="font-medium">24-25 Course Syllabus</div>
                            <div class="text-sm text-yellow-600">Pending</div>
                        </div>
                        <div class="text-sm text-gray-500">Submitted by Altea, Laura</div>
                        <div class="text-xs text-gray-400 mt-1">Due in 2 days</div>
                    </div>
                    <div class="p-3 bg-gray-50 rounded-lg">
                        <div class="flex justify-between items-center">
                            <div class="font-medium">Faculty Assessment Form</div>
                            <div class="text-sm text-yellow-600">Pending</div>
                        </div>
                        <div class="text-sm text-gray-500">Submitted by Alvarez, Jhun</div>
                        <div class="text-xs text-gray-400 mt-1">Due in 5 days</div>
                    </div>
                </div>
            </div>

            <!-- Member Growth Panel -->
            <div class="bg-white p-[30px] rounded-lg shadow-md">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-bold font-overpass">Member Growth</h2>
                    <div class="flex items-center space-x-2">
                        <select id="growthPeriod" class="text-sm border rounded p-1">
                            <option value="year">Year</option>
                            <option value="quarter">Quarter</option>
                            <option value="month">Month</option>
                        </select>
                    </div>
                </div>
                
                <!-- Member Growth Chart -->
                <div>
                    <canvas id="memberGrowthChart" height="200"></canvas>
                </div>
                
                <!-- Growth Metrics -->
                <div class="grid grid-cols-2 gap-4 mt-6">
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-sm text-gray-500">Growth Rate</div>
                        <div class="text-xl font-bold text-green-600">+15.2%</div>
                        <div class="text-xs text-gray-500">vs. last period</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded-lg">
                        <div class="text-sm text-gray-500">Retention Rate</div>
                        <div class="text-xl font-bold text-blue-600">94%</div>
                        <div class="text-xs text-gray-500">Last 12 months</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show toast notifications
        function showToast(message, type) {
            var toast = document.createElement("div");
            toast.classList.add("toast", type); // Add the type for styling
            toast.innerText = message;
            document.body.appendChild(toast);

            // Automatically hide the toast after 3 seconds
            setTimeout(function() {
                toast.classList.add("fade-out");
                setTimeout(function() {
                    toast.remove();
                }, 500);
            }, 3000);
        }

        // Initialize charts when document is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Faculty Department Distribution Chart
            const facultyDeptCtx = document.getElementById('facultyDeptChart').getContext('2d');
            const facultyDeptChart = new Chart(facultyDeptCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Networking', 'Application Development', 'Web Development', 'Advanced Database', 'Integrative'],
                    datasets: [{
                        data: [35, 20, 25, 15, 18],
                        backgroundColor: [
                            '#4F46E5',
                            '#10B981',
                            '#F59E0B',
                            '#EF4444',
                            '#8B5CF6'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });

            // Reviews Chart
            const reviewsCtx = document.getElementById('reviewsChart').getContext('2d');
            const reviewsChart = new Chart(reviewsCtx, {
                type: 'bar',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Completed',
                        data: [12, 19, 13, 15, 20, 10],
                        backgroundColor: '#10B981',
                        barThickness: 12,
                        borderRadius: 4
                    }, {
                        label: 'Pending',
                        data: [5, 8, 3, 7, 9, 7],
                        backgroundColor: '#F59E0B',
                        barThickness: 12,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            },
                            ticks: {
                                stepSize: 5
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 12,
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });

            // Member Growth Chart
            const growthCtx = document.getElementById('memberGrowthChart').getContext('2d');
            const memberGrowthChart = new Chart(growthCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Total Members',
                        data: [102, 108, 115, 120, 125, 130, 135, 138, 142, 145, 148, 152],
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderColor: '#4F46E5',
                        borderWidth: 2,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#4F46E5',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#f3f4f6'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Handle period change for member growth chart
            document.getElementById('growthPeriod').addEventListener('change', function() {
                const period = this.value;
                let labels, data;
                
                if (period === 'year') {
                    labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                    data = [102, 108, 115, 120, 125, 130, 135, 138, 142, 145, 148, 152];
                } else if (period === 'quarter') {
                    labels = ['Q1', 'Q2', 'Q3', 'Q4'];
                    data = [115, 130, 142, 152];
                } else if (period === 'month') {
                    labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
                    data = [148, 149, 151, 152];
                }
                
                memberGrowthChart.data.labels = labels;
                memberGrowthChart.data.datasets[0].data = data;
                memberGrowthChart.update();
            });
        });
    </script>

</body>
</html>