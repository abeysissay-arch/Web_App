require('dotenv').config();
const express = require('express');
const cors = require('cors');
const mysql = require('mysql2');

const app = express();
const PORT = process.env.NODEJS_PORT || 3000;

// Detect environment
const isDocker = process.env.DOCKER_ENV === 'true' || process.env.DB_HOST === 'mysql';
const environment = isDocker ? 'Docker' : 'Local';

console.log(`🚀 Starting eLearning API in ${environment} environment`);

// Middleware
app.use(cors());
app.use(express.json());

// MySQL connection with both Docker and local support
const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  user: process.env.MYSQL_USER,
  password: process.env.MYSQL_PASSWORD,
  database: process.env.MYSQL_DATABASE || 'elearning',
  port: process.env.MYSQL_PORT || 3307,
  connectTimeout: 60000,
  reconnect: true,
  acquireTimeout: 60000,
  timeout: 60000
};

console.log('🔧 Database Configuration:');
console.log('   Host:', dbConfig.host);
console.log('   Database:', dbConfig.database);
console.log('   User:', dbConfig.user);
console.log('   Port:', dbConfig.port);

const db = mysql.createConnection(dbConfig);

// Connect to MySQL with retry logic
const connectWithRetry = (retryCount = 0) => {
  const maxRetries = isDocker ? 10 : 3;
  const retryDelay = isDocker ? 5000 : 2000;
  
  db.connect((err) => {
    if (err) {
      console.error(`❌ MySQL connection failed (attempt ${retryCount + 1}/${maxRetries}):`, err.message);
      
      if (retryCount < maxRetries) {
        console.log(`🔄 Retrying in ${retryDelay/1000} seconds...`);
        setTimeout(() => connectWithRetry(retryCount + 1), retryDelay);
      } else {
        console.error('💥 Maximum connection retries reached. Exiting...');
        process.exit(1);
      }
    } else {
      console.log('✅ Connected to MySQL database');
    }
  });
};

connectWithRetry();

// Handle database disconnections
db.on('error', (err) => {
  console.error('❌ Database error:', err);
  if (err.code === 'PROTOCOL_CONNECTION_LOST') {
    console.log('🔄 Reconnecting to database...');
    connectWithRetry();
  } else {
    throw err;
  }
});

// Routes
app.get('/', (req, res) => {
    res.json({ 
        message: 'eLearning API Server with MySQL is running!',
        environment: environment,
        database: {
            host: dbConfig.host,
            name: dbConfig.database,
            user: dbConfig.user
        },
        endpoints: {
            courses: '/api/courses',
            courseById: '/api/courses/:id',
            users: '/api/users',
            enrollments: '/api/enrollments',
            health: '/health'
        }
    });
});

// Get all courses
app.get('/api/courses', (req, res) => {
    const query = 'SELECT * FROM courses ORDER BY created_at DESC';
    
    db.query(query, (err, results) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Failed to fetch courses' });
        }
        res.json(results);
    });
});

// Get course by ID
app.get('/api/courses/:id', (req, res) => {
    const courseId = req.params.id;
    const query = 'SELECT * FROM courses WHERE id = ?';
    
    db.query(query, [courseId], (err, results) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Failed to fetch course' });
        }
        
        if (results.length === 0) {
            return res.status(404).json({ error: 'Course not found' });
        }
        
        res.json(results[0]);
    });
});

// Get all users
app.get('/api/users', (req, res) => {
    const query = 'SELECT id, username, email, created_at FROM users';
    
    db.query(query, (err, results) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Failed to fetch users' });
        }
        res.json(results);
    });
});

// Get enrollments
app.get('/api/enrollments', (req, res) => {
    const query = `
        SELECT e.id, u.username, c.title, e.enrolled_at 
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        ORDER BY e.enrolled_at DESC
    `;
    
    db.query(query, (err, results) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Failed to fetch enrollments' });
        }
        res.json(results);
    });
});

// Create new course
app.post('/api/courses', (req, res) => {
    const { title, instructor, duration, price } = req.body;
    
    if (!title || !instructor) {
        return res.status(400).json({ error: 'Title and instructor are required' });
    }
    
    const query = 'INSERT INTO courses (title, instructor, duration, price) VALUES (?, ?, ?, ?)';
    
    db.query(query, [title, instructor, duration || 0, price || 0], (err, results) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ error: 'Failed to create course' });
        }
        
        res.status(201).json({
            id: results.insertId,
            title,
            instructor,
            duration: duration || 0,
            price: price || 0,
            message: 'Course created successfully'
        });
    });
});

// Health check with database connection test
app.get('/health', (req, res) => {
    db.query('SELECT 1 as test', (err, results) => {
        if (err) {
            return res.status(500).json({ 
                status: 'ERROR', 
                database: 'DISCONNECTED',
                error: err.message,
                environment: environment
            });
        }
        
        res.json({ 
            status: 'OK', 
            database: 'CONNECTED',
            timestamp: new Date().toISOString(),
            environment: environment
        });
    });
});

// Enrollment endpoint
app.post('/api/enroll', (req, res) => {
    console.log('Enrollment request received:', req.body);
    
    const { courseId, studentName, studentEmail } = req.body;
    
    // Validation
    if (!courseId || !studentName || !studentEmail) {
        return res.status(400).json({ 
            success: false,
            error: 'Course ID, student name, and email are required' 
        });
    }
    
    // First, check if course exists in database
    const checkCourseQuery = 'SELECT id, title FROM courses WHERE id = ?';
    
    db.query(checkCourseQuery, [courseId], (err, courseResults) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ 
                success: false,
                error: 'Database error: ' + err.message 
            });
        }
        
        if (courseResults.length === 0) {
            return res.status(404).json({ 
                success: false,
                error: 'Course not found' 
            });
        }
        
        const course = courseResults[0];
        
        // Check if user exists, if not create one
        const findUserQuery = 'SELECT id FROM users WHERE email = ?';
        
        db.query(findUserQuery, [studentEmail], (err, userResults) => {
            if (err) {
                console.error('Database error:', err);
                return res.status(500).json({ 
                    success: false,
                    error: 'Database error: ' + err.message 
                });
            }
            
            let userId;
            
            if (userResults.length > 0) {
                // User exists, use their ID
                userId = userResults[0].id;
                createEnrollment(userId, courseId, res, course.title, studentName, studentEmail);
            } else {
                // Create new user
                const createUserQuery = 'INSERT INTO users (username, email) VALUES (?, ?)';
                
                db.query(createUserQuery, [studentName, studentEmail], (err, userInsertResult) => {
                    if (err) {
                        console.error('Database error:', err);
                        return res.status(500).json({ 
                            success: false,
                            error: 'Failed to create user: ' + err.message 
                        });
                    }
                    
                    userId = userInsertResult.insertId;
                    createEnrollment(userId, courseId, res, course.title, studentName, studentEmail);
                });
            }
        });
    });
});

// Helper function to create enrollment
function createEnrollment(userId, courseId, res, courseTitle, studentName, studentEmail) {
    const enrollmentQuery = 'INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)';
    
    db.query(enrollmentQuery, [userId, courseId], (err, enrollmentResult) => {
        if (err) {
            console.error('Database error:', err);
            return res.status(500).json({ 
                success: false,
                error: 'Failed to create enrollment: ' + err.message 
            });
        }
        
        const enrollment = {
            id: enrollmentResult.insertId,
            userId: userId,
            courseId: parseInt(courseId),
            studentName: studentName,
            studentEmail: studentEmail,
            enrolledAt: new Date().toISOString(),
            courseTitle: courseTitle
        };
        
        console.log('New enrollment created:', enrollment);
        
        res.status(201).json({
            success: true,
            message: 'Successfully enrolled in the course!',
            enrollment: enrollment
        });
    });
}

// Error handling middleware
app.use((err, req, res, next) => {
    console.error('Unhandled error:', err);
    res.status(500).json({ 
        success: false,
        error: 'Internal server error',
        environment: environment
    });
});

// 404 handler
app.use((req, res) => {
    res.status(404).json({ 
        success: false,
        error: 'Endpoint not found',
        availableEndpoints: {
            courses: '/api/courses',
            users: '/api/users',
            enrollments: '/api/enrollments',
            health: '/health'
        }
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`🎯 Node.js API Server running at http://localhost:${PORT}`);
    console.log(`🌍 Environment: ${environment}`);
    console.log(`🗄️  Database: ${dbConfig.database} @ ${dbConfig.host}:${dbConfig.port}`);
    console.log(`📚 Available endpoints:`);
    console.log(`   GET /api/courses`);
    console.log(`   GET /api/courses/:id`);
    console.log(`   GET /api/users`);
    console.log(`   GET /api/enrollments`);
    console.log(`   POST /api/courses`);
    console.log(`   POST /api/enroll`);
    console.log(`   GET /health`);
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🛑 Shutting down gracefully...');
    db.end((err) => {
        if (err) {
            console.error('Error closing database connection:', err);
        } else {
            console.log('✅ Database connection closed');
        }
        process.exit(0);
    });
});

process.on('SIGTERM', () => {
    console.log('\n🛑 Received SIGTERM, shutting down...');
    db.end();
    process.exit(0);
});