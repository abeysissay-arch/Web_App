<!DOCTYPE html>
<html>
<head>
    <title>eLearning Platform</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .course { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .header { background: #4CAF50; color: white; padding: 20px; border-radius: 5px; }
        .enroll-btn { background: #4CAF50; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 3px; }
        .enroll-btn:hover { background: #45a049; }
        .modal { display: none;/* This hides the entire modal by default */ position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 300px; border-radius: 5px; }
        .form-group { margin: 10px 0; }
        label { display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 3px; }
        .success { color: green; margin: 10px 0; }
        .error { color: red; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>📚 eLearning Platform</h1>
        <p>Welcome to our learning management system</p>
         <a href="students.php" style="color: white; text-decoration: underline;">View Registered Students</a>
    </div>

    <h2>Available Courses</h2>
    <div id="courses">
        <p>Loading courses...</p>
    </div>

    <!-- Enrollment Modal -->
    <div id="enrollModal" class="modal"><!-- moldal is from style  -->
        <div class="modal-content">
            <h3>Enroll in Course</h3>
            <form id="enrollForm">
                <input type="hidden" id="enrollCourseId">
                <div class="form-group">
                    <label for="studentName">Full Name:</label>
                    <input type="text" id="studentName" required>
                </div>
                <div class="form-group">
                    <label for="studentEmail">Email:</label>
                    <input type="email" id="studentEmail" required>
                </div>
                <button type="submit" class="enroll-btn">Complete Enrollment</button>
                <button type="button" onclick="closeModal()">Cancel</button>
            </form>
            <div id="enrollMessage"></div>
        </div>
    </div>

    <script>
        let currentCourseId = null;

        // Fetch courses from Node.js API
        async function loadCourses() {
            try {
                const response = await fetch('http://localhost:3000/api/courses');// Use relative path - will be proxied through PHP or use same origin
                //const response = await fetch('/api/proxy?endpoint=/api/courses');
                const courses = await response.json();
                
                const coursesContainer = document.getElementById('courses');
                coursesContainer.innerHTML = '';
                
                courses.forEach(course => {
                    const courseDiv = document.createElement('div');
                    courseDiv.className = 'course';
                    courseDiv.innerHTML = `
                        <h3>${course.title}</h3>
                        <p><strong>Instructor:</strong> ${course.instructor}</p>
                        <p><strong>Duration:</strong> ${course.duration} hours</p>
                        <p><strong>Price:</strong> $${course.price}</p>
                        <button class="enroll-btn" onclick="openEnrollModal(${course.id})">Enroll Now</button>
                    `;
                    coursesContainer.appendChild(courseDiv);
                });
            } catch (error) {
                console.error('Error loading courses:', error);
                document.getElementById('courses').innerHTML = '<p>Error loading courses. Make sure the Node.js server is running.</p>';
            }
        }

        // Open enrollment modal
        function openEnrollModal(courseId) {
            currentCourseId = courseId;
            document.getElementById('enrollCourseId').value = courseId;
            document.getElementById('enrollModal').style.display = 'block'; // Shows popup // This makes it visible!
            document.getElementById('enrollMessage').innerHTML = '';
            document.getElementById('studentName').value = '';
            document.getElementById('studentEmail').value = '';
        }

        // Close modal
        function closeModal() {
            document.getElementById('enrollModal').style.display = 'none';//hide
        }

        // Handle enrollment form submission
        document.getElementById('enrollForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const studentName = document.getElementById('studentName').value;
            const studentEmail = document.getElementById('studentEmail').value;
            
            try {
                const response = await fetch('enroll.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        courseId: currentCourseId,
                        studentName: studentName,
                        studentEmail: studentEmail
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('enrollMessage').innerHTML = 
                        `<div class="success">${result.message}</div>`;
                    setTimeout(() => {
                        closeModal();
                    }, 2000);
                } else {
                    document.getElementById('enrollMessage').innerHTML = 
                        `<div class="error">${result.error}</div>`;
                }
            } catch (error) {
                console.error('Enrollment error:', error);
                document.getElementById('enrollMessage').innerHTML = 
                    `<div class="error">Network error. Please try again.</div>`;
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('enrollModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Load courses when page loads
        loadCourses();
    </script>
</body>
</html>