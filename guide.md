
# For the mailing service to work u have to follow the installation guide

# 1. *Install Composer*

## Download Composer

Go to the official website: and choose what suits ur OS
https://getcomposer.org/download/



## 2. Install PHPMailer

##  Go to your project folder

```bash
example cd PennyWise_backend
```

## 3. Google Setup for PHPMailer (Gmail SMTP)
Enable 2-Step Verification

You must enable 2-Step Verification first.

A . *Go to:*
https://myaccount.google.com/security

- Scroll to **"Signing in to Google"**
- Enable **2-Step Verification**

---

 B . *Create App Password*

After enabling 2-Step Verification:

👉 Go to:
https://myaccount.google.com/apppasswords

 Steps:

1. Select **App** → Mail  
2. Select **Device** → Other (Custom name)  
3. Enter a name like:
Click **Generate**


*C. Copy the Password*

Google will generate a **16-character password** like:
sdda asaf hjeb jyte (use it without the spacing)



D. * enter the copied password to ur  .env


*DONE!*
