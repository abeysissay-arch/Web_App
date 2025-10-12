<!DOCTYPE html>
<html>
<head>
    <title>Registered Students - eLearning</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { background: #2196F3; color: white; padding: 20px; border-radius: 5px; }
        .student { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .back-btn { background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <h1>👥 Registered Students</h1>
        <p>View all enrolled students and their courses</p>
    </div>

    <a href="index.php" class="back-btn">← Back to Courses</a>

    <h2>Enrollment Records</h2>
    <div id="enrollments">
        <p>Loading enrollment data...</p>
    </div>

    <script>
        async function loadEnrollments() {
            try {
                const response = await fetch('http://localhost:3000/api/enrollments');
                const enrollments = await response.json();
                
                const container = document.getElementById('enrollments');
                
                if (enrollments.length === 0) {
                    container.innerHTML = '<p>No enrollments found.</p>';
                    return;
                }
                
                // Create table
                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Course</th>
                                <th>Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                enrollments.forEach(enrollment => {
                    html += `
                        <tr>
                            <td>${enrollment.username}</td>
                            <td>${enrollment.title}</td>
                            <td>${new Date(enrollment.enrolled_at).toLocaleDateString()}</td>
                        </tr>
                    `;
                });
                
                html += `</tbody></table>`;
                container.innerHTML = html;
                
            } catch (error) {
                console.error('Error loading enrollments:', error);
                document.getElementById('enrollments').innerHTML = 
                    '<p>Error loading enrollment data. Make sure the Node.js server is running.</p>';
            }
        }

        // Load enrollments when page loads
        loadEnrollments();
    </script>
</body>
</html>