// api/get-data.js - Retrieve captured data

export default async function handler(req, res) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.status(200).end();
        return;
    }

    if (req.method !== 'GET') {
        res.status(405).json({ success: false, message: 'Method not allowed' });
        return;
    }

    // Password protection
    const adminPassword = process.env.ADMIN_PASSWORD || 'admin123';
    const providedPassword = req.query.password || req.headers['x-admin-password'];

    if (providedPassword !== adminPassword) {
        res.status(401).json({ success: false, message: 'Unauthorized' });
        return;
    }

    // Return captured data (from your database)
    res.status(200).json({
        success: true,
        data: [],
        message: 'Configure your database to see data here'
    });
}
