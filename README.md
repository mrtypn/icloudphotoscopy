# Overview
The purpose of this utility is to download all your icloud photos to your local hard drive. You do not need to save it to your IPhotos or download any software.
# Main features
- Multiple platform supports. You can run it in Windows/Mac/Linux
- Multiple process - download it faster , you can control the number of process to run depending on your network/cpu capacity
- Resume - whenever you run , it will only download new files only. It does not delete anything.
- Organize the folders - you can put the photos by months or days.
- Extra data: each of the file will also come with a json file, this is your photo meta folder.

# How to Use

In order to use  this the utility need the cookie from your icloud session.  Here are the steps to do:
  - Open Google Chrome
  - Login your  Icloud Photos (https://icloud.com) , it's recommnended to click on "Keep Me Sign In" so the session can last login.
  - Right click any where in the browser, select "Inspect" , click on "Network" tab
  - In the address bar paste this link: https://www.icloud.com/photos/.  (you can click on Photos icon as well)
  - In the Inspect window, right click on "/photos" , select Copy -> Copy as cCurl.   
  - go to your application folder, create a new file "curl.txt" and paste the content #DONT SHARE THIS FILE, MAKE SURE THAT ONLY YOU CAN ACCESS IT
  - Now, you can run the utility
  - For Mac/Linux: open the console ,  go to the utility folder and run: bash linux_mac_start.sh
  - For Windows: double click on "win_start.bat"       In Windows, if you need to stop, please run "win_stop.bat"
  
# Configuration 

All the configruation are in file : config.php
There are 3 main configration lines you can change:

- $BACKUP_DIR='J:\my_icloud_photos\photos';   // this is where the photos will be saved, if there is no such folder it will create "photos" in the utility folder
- $CURL_FILE="J:\my_icloud_photos\curl.txt";  //this is the location of curl file, by default, it will use "curl.txt" in the utility folder - DONT SHARE THIS FILE as if anyone has this file, they can access your icloud photos

- $MAX_JOBS=2;  //number of job to download your photos
- $BACKUP_DIR_FOLDER_FORMAT_BY_MONTH = false; // Group by month or day value: true/false , default: false means group by day


