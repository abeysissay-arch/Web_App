// Quick test for the API
const fetch = require('node-fetch'); // You might need to install: npm install node-fetch

async function testAPI() {
    try {
        const response = await fetch('http://localhost:3000/api/courses');
        const courses = await response.json();
        console.log('✅ API Test Successful!');
        console.log('Courses:', courses);
    } catch (error) {
        console.log('❌ API Test Failed:', error.message);
    }
}

testAPI();