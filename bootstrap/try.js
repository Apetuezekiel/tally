const sendSMSTermii = async(phone)=> {
    phone = phone.split(",").map(item => item.replace(/\s/g, "").slice(-10));
    phone = phone.map(item => `+234${item}`);
    const otp = await generateOTP(phone[0]);
    let message = `NetPlus\nHello, your confirmation code is ${otp}`;

    let data = {
        to : phone,
        // pending approval of our Sender ID
        // from: "Netplus",
        from: "N-Alert",
        sms: message,
        type: "plain",
        channel: "dnd",
        api_key: process.env.TERMII_API_KEY
    }
    let options = {
        method: 'POST',
        url: `${process.env.TERMII_BASE_URL}`,
        headers: {
            "Content-Type": ['application/json', 'application/json']
        },
        body: JSON.stringify(data)
    }
    request(options, (error, response) => {
        // this is not returning 'anything'
        return true;
    });
    return true;
}

let a;


function doSomething(){
    a = 123;
    return a;
}
