**ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT** 

**สุธนัย จันทร์ประเสริฐ** 

**โครงงานนี้เป็นส่วนหนึ่งของการศึกษาหลักสูตรปริญญาวิทยาศาสตรบัณฑิต สาขาวิชาเทคโนโลยีสารสนเทศและการสื่อสาร** 

**คณะวิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคลตะวันออก** 

**พ.ศ. 2568** 

**ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT** 

**สุธนัย จันทร์ประเสริฐ** 

**โครงงานนี้เป็นส่วนหนึ่งของการศึกษาหลักสูตรปริญญาวิทยาศาสตรบัณฑิต สาขาวิชาเทคโนโลยีสารสนเทศและการสื่อสาร** 

**คณะวิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคลตะวันออก** 

**พ.ศ. 2568** 

**Stock Supply : IT Asset and Equipment Management System** 

###### **Mr. Sutanai Janprasert** 

**A project submitted in partial fulfillment of the requirement for the Bachelor Degree of Program in Information and Communication Technology Department of Information and Communication Technology Faculty of Science and Technology Rajamangala University of Technology Tawan-ok** 

**2025** 

ก 

###### **คณะกรรมการรับรองโครงงาน** 

คณะกรรมการรับรองโครงงาน ได้พิจารณาโครงงานของ นายสุธนัย จันทร์ประเสริฐ ฉบับนี้ แล้ว เห็นสมควรรับเป็นส่วนหนึ่งของการศึกษาตามหลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาเทคโนโลยี สารสนเทศและการสื่อสาร คณะวิทยาศาสตร์และเทคโนโลยี มหาวิทยาลัยเทคโนโลยีราชมงคล ตะวันออก 

<u>อาจารย์ที่ปรึกษาหลัก</u> ( ) 

คณะกรรมการสอบโครงงาน 

||ประธาน|
|---|---|
|(|)|
||กรรมการ|
|(|)|
||กรรมการ|
|(|)|



สาขาวิชาเทคโนโลยีสารสนเทศและการสื่อสาร อนุมัติให้รับโครงงานฉบับนี้เป็นส่วนหนึ่งของ การศึกษาตามหลักสูตรวิทยาศาสตรบัณฑิต สาขาวิชาเทคโนโลยีสารสนเทศและการสื่อสาร 

<u>หัวหน้าสาขาวิชาฯ</u> 

( ) 

ข 

###### **กิตติกรรมประกาศ (Acknowledgement)** 

การที่ผู้จัดทำได้มาปฏิบัติงานในโครงการสหกิจศึกษา ณ บริษัท เดอะ บิสซิเนส เอสอีโอ จำกัด ตั้งแต่วันที่ 24 มีนาคม พ.ศ. 2568 ถึงวันที่ 24 กันยายน พ.ศ. 2568 ผู้จัดโครงงานได้ทำ โครงงานเรื่อง ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ซึ่งทำให้ได้รับประสบการณ์ต่าง ๆ ใน การทำงาน ที่มีคุณค่าสำหรับการพัฒนาตนเอง และวิชาชีพ สำหรับโครงงานฉบับนี้สำเร็จลงได้ด้วยดี จากความช่วยเหลือจาก อาจารย์ สุกัลยา ชาญสมร ซึ่งเป็นอาจารย์ที่ปรึกษาโครงงานได้ให้คำปรึกษา และข้อเสนอแนะเพื่อจัดทำโครงงานจนโครงงานเล่มนี้เสร็จสิ้น ผู้จัดทำจึงขอกราบขอบพระคุณเป็น อย่างสูง 

ขอขอบพระคุณ คุณกชกร เกตุติวัฒนพงศ์ ซึ่งเป็นเจ้าหน้าที่และพี่เลี้ยงภายในองค์กรบริษัท เดอะ บิสซิเนส เอสอีโอ จำกัด ที่ได้ให้คำแนะนำช่วยเหลือในการจัดทำโครงงาน 

นายสุธนัย จันทร์ประเสริฐ สิงหาคม 2568 

ค 

###### **บทคัดย่อ** 

บริษัทประสบปัญหาในการบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT โดยใช้การบันทึก ข้อมูลผ่านโปรแกรม Excel ซึ่งส่งผลให้เกิดข้อผิดพลาดและความล่าช้าในการติดตามสถานะอุปกรณ์ เพื่อแก้ไขปัญหาดังกล่าว จึงได้พัฒนาเว็บไซต์ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT สำหรับองค์กรขึ้น โดยใช้ WordPress เป็นแพลตฟอร์มหลักสำหรับพัฒนาเว็บไซต์ พร้อมกับออกแบบ ฐานข้อมูลด้วย SQL และออกแบบส่วนติดต่อผู้ใช้ด้วย Figma เพื่อให้ได้รูปแบบที่ใช้งานง่ายและตอบ โจทย์ความต้องการของผู้ใช้ 

ระบบนี้ช่วยให้การบันทึกข้อมูล การยืม–คืน การบำรุงรักษา และการจัดการอุปกรณ์เป็นไป อย่างเป็นระบบและมีประสิทธิภาพมากขึ้น จากการวิเคราะห์พบว่าการนำระบบมาใช้ช่วยลด ข้อผิดพลาดในการจัดเก็บข้อมูลและเพิ่มความรวดเร็วในการประมวลผลข้อมูลได้อย่างมีนัยสำคัญ 

ผลลัพธ์จากการดำเนินงานพบว่าเว็บไซต์ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ช่วยให้บริษัทสามารถติดตามและควบคุมสถานะของอุปกรณ์ได้อย่างแม่นยำและเป็นระบบมากขึ้น ลด เวลาและแรงงานในการจัดการข้อมูล อีกทั้งยังเพิ่มความสะดวกในการตรวจสอบประวัติการใช้งาน และบำรุงรักษาอุปกรณ์ 

ประโยชน์ที่ได้รับส่งผลให้การบริหารสินทรัพย์เป็นไปอย่างมีประสิทธิภาพและโปร่งใส สำหรับ ข้อเสนอแนะ ควรพัฒนาฟีเจอร์เพิ่มเติม เช่น ระบบแจ้งเตือนการบำรุงรักษาอุปกรณ์ และระบบ รายงานเชิงวิเคราะห์ เพื่อยกระดับการจัดการและสนับสนุนการตัดสินใจในองค์กรให้ดียิ่งขึ้น 

**คำสำคัญ:** ระบบบริหารจัดการสินทรัพย์, คลังอุปกรณ์ IT, WordPress, การบำรุงรักษา 

ง 

###### **Abstract** 

The company faced difficulties managing IT assets and inventory using Excel, which caused errors and delays in tracking equipment status. To address this, a webbased IT asset management system was developed using WordPress, with a SQL database and a user interface designed in Figma. This system streamlines recording, borrowing, returning, maintenance, and overall asset management, reducing errors and improving efficiency. 

The system allows accurate monitoring and control of equipment status, saving time and effort while enhancing access to usage and maintenance history. These improvements lead to more efficient and transparent asset management. Future enhancements should include maintenance alerts and analytical reporting to support better decision-making. 

**Keywords:** Asset Management System, IT Equipment Inventory, WordPress, Maintenance 

จ 

###### **สารบัญเรื่อง (Table of Contents)** 

|||**หนา**|
|---|---|---|
|**คณะกรรมกา**|**รรบรองโครงงาน**|**ก**|
|**กตตกรรมปร**|**ะกาศ (Acknowledgement)**|**ข**|
|**บทคดยอ**||**ค**|
|**Abstract**||**ง**|
|**สารบญเรอง**|**(Table of Contents)**|**จ**|
|**สารบญตารา**|**ง (List of Tables)**|**ช**|
|**สารบญภาพ**|**(List of Illustrations)**|**ซ**|
|**คำอธบายสญ**|**ลกษณและคำยอทใชในการวจย**|**ฏ**|
|**บทท 1**|**บทนำ**|**1**|
||**1.1 ทมาและความสำคญ**|**1**|
||**1.2 วตถประสงคของโครงงาน**|**1**|
||**1.3 ขอบเขตโครงงาน**|**1**|
||**1.4 ทรพยากรทใชในโครงงาน**|**2**|
||**1.5 ระยะเวลาททำโครงงาน**|**3**|
||**1.6 ประโยชนทคาดวาจะไดรบ**|**3**|
|**บทท 2**|**ทฤษฎและงานทเกยวของ**|**4**|
||**2.1 WordPress**|**4**|
||**2.2 WordPress Plugin**|**5**|
||**2.3 Astra Theme**|**5**|
||**2.4 Beaver Builder**|**7**|
||**2.5 Custom Sidebars**|**8**|
||**2.6 LoginPress**|**10**|
||**2.7 Visual Studio Code**|**11**|
||**2.8 FTP FileZilla**|**13**|
||**2.9 Figma**|**14**|
||**2.10 phpMyAdmin**|**15**|
||**2.11 Bootstrap**|**17**|



ฉ 

###### **สารบัญเรื่อง (ต่อ)** 

|||**หนา**|
|---|---|---|
||**2.12 ภาษาโปรแกรมทเกยวของ**|**19**|
|**บทท 3**|**วธดำเนนโครงการ**|**23**|
||**3.1 การออกแบบสวนตดตอผใช (User Interface Design)**|**23**|
||**3.2 แผนภาพแสดงการทำงานของผใชระบบ (Use Case Diagram)**|**34**|
||**3.3 แผนภาพแสดงลำดบการทำงานของระบบ (Sequence Diagram)**|**35**|
||**3.4 แผนภาพแสดงความสมพนธระหวางเอนทต (Entity**|**39**|
||**Relationship Diagram)**||
|**บทท 4**|**ผลการดำเนนงาน**|**40**|
||**4.1 ภาพรวมของเวบไซต Stock Supply**|**40**|
||**4.2 ผลการประเมนโครงงานโดยผพฒนาจากการทดสอบระบบโดย**|**49**|
||**ผพฒนา**||
||**4.3 ผลการประเมนโครงงานโดยกลมผดแลระบบ**|**50**|
|**บทท 5**|**สรปผลการดำเนนโครงงานและขอเสนอแนะ**|**52**|
||**5.1 สรปผลการดำเนนงาน**|**52**|
||**5.2 สรปผลการประเมนโดยกลมผดแลระบบ**|**53**|
||**5.3 ขอจำกดของโครงการ**|**54**|
||**5.4 ขอเสนอแนะในการพฒนา**|**55**|
|**บรรณานกรม**||**56**|
|**ภาคผนวก**||**57**|
|**ก**|**คมอการใชงานระบบ : ผดแลระบบและผทไดรบมอบหมาย**|**58**|
||**ประวตผจดทำโครงงาน**|**74**|



ช 

###### **สารบัญตาราง (List of Tables)** 

|||**หนา**|
|---|---|---|
|**ตารางท 1.1**|**ระยะเวลาในการดำเนนงาน**|**3**|
|**ตารางท 4.1**|**ตารางสรปการประเมนโครงงานโดยกลมผดแลระบบ**|**50**|
|**ตารางท 5.1**|**ตารางสรปผลทดสอบโดยกลมผดแลระบบและผจดการ**|**53**|



ซ 

###### **สารบัญภาพ (List of Illustrations)** 

|**ภาพท 2.1**|**หนาหลก**|**หนา**<br>**4**|
|---|---|---|
|**ภาพท 2.2**|**Astra Theme**|**6**|
|**ภาพท 2.3**|**Beaver Builder**|**8**|
|**ภาพท 2.4**|**Visual Studio Code**|**13**|
|**ภาพท 2.5**|**FTP FileZilla**|**14**|
|**ภาพท 2.6**|**Figma**|**15**|
|**ภาพท 2.7**|**phpMyAdmin**|**17**|
|**ภาพท 2.8**|**Bootstrap**|**19**|
|**ภาพท 2.9**|**PHP**|**20**|
|**ภาพท 2.10**|**HTML**|**20**|
|**ภาพท 2.11**|**CSS**|**21**|
|**ภาพท 2.12**|**JavaScript**|**22**|
|**ภาพท 3.1**|**หนา Login**|**23**|
|**ภาพท 3.2**|**หนา Dashboard**|**24**|
|**ภาพท 3.3**|**หนา Monitor**|**25**|
|**ภาพท 3.4**|**หนา Laptop**|**26**|
|**ภาพท 3.5**|**หนา Accessories**|**27**|
|**ภาพท 3.6**|**หนา Maintenance**|**28**|
|**ภาพท 3.7**|**หนา History**|**29**|
|**ภาพท 3.8**|**หนา Employee**|**30**|
|**ภาพท 3.9**|**หนา Add Device**|**31**|
|**ภาพท 3.10**|**หนา Edit Device**|**31**|
|**ภาพท 3.11**|**หนา View Details**|**32**|
|**ภาพท 3.12**|**หนา Form Maintenance**|**33**|
|**ภาพท 3.13**|**Action Status**|**33**|
|**ภาพท 3.14**|**แผนภาพแสดงการทำงานของผใชงานระบบ**|**34**|
|**ภาพท 3.15**|**UC01 เขาสระบบ (Login)**|**35**|



ฌ 

###### **สารบัญภาพ (ต่อ)** 

|||**หนา**|
|---|---|---|
|**ภาพท 3.16**|**UC2 เพม/ลบ/แกไขขอมลพนกงาน**|**35**|
|**ภาพท 3.17**|**UC3 เพม/ลบ/แกไขขอมลอปกรณ**|**36**|
|**ภาพท 3.18**|**UC4 จดการยม-คนอปกรณ**|**36**|
|**ภาพท 3.19**|**UC5 ดแลการบำรงรกษา**|**37**|
|**ภาพท 3.20**|**UC6 หนาเพมขอมลผใช**|**37**|
|**ภาพท 3.21**|**UC7 หนา History**|**38**|
|**ภาพท 3.22**|**UC8 หนา View Details**|**38**|
|**ภาพท 3.23**|**แผนภาพแสดงความสมพนธระหวางเอนทต**|**39**|
|**ภาพท 4.1**|**หนา Login**|**40**|
|**ภาพท 4.2**|**หนาหลก**|**41**|
|**ภาพท 4.3**|**หนาขอมล Monitor**|**42**|
|**ภาพท 4.4**|**หนาขอมล Laptop**|**43**|
|**ภาพท 4.5**|**หนาขอมล Accessories**|**44**|
|**ภาพท 4.6**|**หนาขอมล Maintenance**|**45**|
|**ภาพท 4.7**|**หนาขอมล History**|**45**|
|**ภาพท 4.8**|**หนาขอมล Employee และ Intern**|**46**|
|**ภาพท 4.9**|**หนาเพมขอมลอปกรณ**|**47**|
|**ภาพท 4.10**|**หนาแกไขขอมลอปกรณ**|**47**|
|**ภาพท 4.11**|**หนารายละเอยดอปกรณและประวตการใชงาน**|**48**|
|**ภาพท 4.12**|**หนากรอกขอมลการยมอปกรณ**|**48**|
|**ภาพท 4.13**|**หนากรอกขอมลการบำรงรกษาอปกรณ**|**49**|



ญ 

###### **คำอธิบายสัญลักษณ์และคำย่อที่ใช้ในการวิจัย (List of Abbreviations)** 

###### **List of Abbreviations** 

|CMS<br>หมายถง|(Content Management System) ระบบจดการเนอหา<br>ของเวบไซต|
|---|---|
|SQL<br>หมายถง|(Structured Query Language) ภาษาสำหรบจดการขอมล|
||ในฐานขอมล|
|WP<br>หมายถง|(WordPress) ระบบจดการเนอหาสำหรบเวบไซต|
|UX<br>หมายถง|(User Experience) ประสบการณผใชงาน|
|UI<br>หมายถง|(User Interface) สวนตอประสานกบผใชงาน|
|IT<br>หมายถง|(Information Technology) เทคโนโลยสารสนเทศ|
|DB<br>หมายถง|(Database) ฐานขอมลทใชเกบขอมลของระบบ|



1 

###### **บทที่ 1** 

###### **บทนำ** 

###### **1.1 ที่มาและความสำคัญ** 

ปัจจุบันบริษัท เดอะ บิสซิเนส เอสอีโอ จำกัด มีการจัดการสินทรัพย์และคลังอุปกรณ์ IT โดย ใช้โปรแกรม Excel ซึ่งวิธีการดังกล่าวส่งผลให้เกิดความผิดพลาดในการบันทึกข้อมูล ติดตามสถานะ อุปกรณ์ได้ยาก และขาดระบบในการบริหารจัดการอย่างเป็นระบบ ทำให้กระบวนการทำงานไม่มี ประสิทธิภาพเท่าที่ควร เพื่อลดข้อผิดพลาดและเพิ่มความแม่นยำในการจัดการ บริษัทจึงมีแนวคิดใน การพัฒนาระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ผ่านเว็บไซต์ที่สามารถจัดเก็บข้อมูล ควบคุม ตรวจสอบสถานะ และประวัติการใช้งานได้อย่างเป็นระบบ ซึ่งจะช่วยให้การดำเนินงานมี ประสิทธิภาพมากยิ่งขึ้น และส่งเสริมการบริหารจัดการที่โปร่งใสและเป็นมืออาชีพ 

###### **1.2 วัตถุประสงค์ของโครงงาน** 

เพื่อพัฒนาเว็บไซต์ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT กรณีศึกษา บริษัท เดอะ บิสซิเนส เอสอีโอ จำกัด โดยมีวัตถุประสงค์ของโครงงาน ดังต่อไปนี้ 

1.2.1 เพื่อพัฒนาเว็บไซต์ที่รองรับการจัดการอุปกรณ์ IT เช่น การเพิ่ม ยืม-คืน ซ่อมบำรุง ลบ และปลดระวางอุปกรณ์ 

1.2.2 เพื่อพัฒนาฐานข้อมูลที่ถูกต้อง พร้อมระบบยืนยันตัวตน และสำรองข้อมูล 

1.2.3 เพื่อเพิ่มประสิทธิภาพการจัดการสินทรัพย์ โดยลดข้อมูลซ้ำซ้อน และลดเวลาในการ ค้นหาข้อมูล 

1.2.4 เพื่อการติดตามและตรวจสอบประวัติการใช้งานและการบำรุงรักษาอุปกรณ์ 

###### **1.3 ขอบเขตโครงงาน** 

###### **1.3.1 ผู้ดูแลระบบ (Admin)** 

1.3.1.1 สามารถเพิ่ม ลบ และแก้ไขข้อมูลอุปกรณ์ 

1.3.1.2 สามารถจัดการการยืม - คืนอุปกรณ์ 

1.3.1.3 สามารถดูแลการบำรุงรักษา 

1.3.1.4 สามารถจัดการบัญชีผู้ใช้งาน (สร้างสิทธิ์ / ลบผู้ใช้) 

1.3.1.5 สามารถตรวจสอบรายการอุปกรณ์ทั้งหมด 

1.3.1.6 สามารถตรวจสอบประวัติการยืมคืนได้ 

2 

- **1.3.2 เจ้าหน้าที่ฝ่าย IT / ผู้รับผิดชอบคลังอุปกรณ์** 

1.3.2.1 สามารถเพิ่ม ลบ และแก้ไขข้อมูลอุปกรณ์ 

1.3.2.2 สามารถจัดการการยืม - คืนอุปกรณ์ 

- 1.3.2.3 สามารถดูแลการบำรุงรักษา 

1.3.2.4 สามารถตรวจสอบรายการอุปกรณ์ทั้งหมด 

- 1.3.2.5 สามารถตรวจสอบประวัติการยืมคืนได้ 

###### **1.4 ทรัพยากรที่ใช้ในโครงงาน** 

1.4.1 ฮาร์ดแวร์ (Hardware) 

1.4.1.1 คอมพิวเตอร์จำนวน 1 เครื่อง 

- หน่วยประมวลผลกลาง Intel i5-10210U CPU @ 1.60GHz   2.11 GHz 

1.4.1.2 หน่วยประมวลผลหลัก RAM 16.00 GB 

   - 1.4.2 ซอฟต์แวร์ (Software) 

- 1.4.2.1 WordPress สำหรับการสร้างเว็บไซต์ 

ปลั๊กอินที่ใช้งาน 

- Astra Pro (ส่วนเสริมของธีม Astra) 

- Beaver Builder 

- Custom Sidebars 

- LoginPress 

- Menu Icons 

###### 1.4.2.2 Programming Language 

- PHP 

- HTML 

- CSS 

- JavaScript 

###### 1.4.2.3 Database 

- phpMyAdmin 

1.4.2.4 เครื่องมือออกแบบ Design Tool 

   - Figma 

- 1.4.2.5 XAMPP Version 3.3.0 

3 

###### 1.4.2.6 FTP FileZilla Version 3.69.1 

###### **1.5 ระยะเวลาที่ทำโครงงาน** 

|<br>|ระยะเวลาดำเนนการ<br>|
|---|---|
|กจกรรม<br>มนาคม 68 เมษายน 68 พฤษภ|าคม 68 มถนายน 68 กรกฎาคม 68 สงหาคม 68 กนยายน 68|
|1 2 3 4 1 2 3 4 1 2|3 4 1 2 3 4 1 2 3 4 1 2 3 4 1 2 3 4|
|1.ศกษาขอมลของโครงงาน||
|2.วางแผนการทำโครงงาน||
|3.ดำเนนโครงการ||
|4.ทดสอบและแกไขระบบ||
|5.จดทำรปเลมโครงงาน||



ตารางที่ 1.1 ระยะเวลาในการดำเนินงาน 

###### **1.6 ประโยชน์ที่คาดว่าจะได้รับ** 

1.6.1 ได้รับความรู้และทักษะจากการพัฒนาเว็บไซต์และฐานข้อมูลจริง 

1.6.2 ได้ฝึกการทำงานร่วมกับทีม การสื่อสาร และการประสานงานกับแผนกต่าง ๆ 

1.6.3 ได้พัฒนาทักษะการแก้ไขปัญหาและการทดสอบระบบจริง 

1.6.4 ได้รับระบบบริหารจัดการสินทรัพย์ที่มีประสิทธิภาพและช่วยลดข้อผิดพลาดในการบัน จัดเก็บข้อมูล 

1.6.5 ระบบที่พัฒนาขึ้นจะช่วยเพิ่มประสิทธิภาพในการบริหารจัดการสินทรัพย์ขององค์กร โดยเน้นความถูกต้อง ความรวดเร็ว และความโปร่งใสในการดำเนินงาน 

## ° WORDPRESS 

5 

###### **2.2 WordPress Plugin** 

ปลั๊กอิน (Plugin) ของ WordPress คือส่วนเสริมที่ช่วยเพิ่มความสามารถและฟีเจอร์ให้กับ เว็บไซต์ ทำให้เว็บไซต์สามารถทำงานได้หลากหลายตามความต้องการของผู้ใช้ โดยทั่วไปปลั๊กอินของ WordPress มีหลายประเภท ครอบคลุมการใช้งานในทุกหมวดหมู่ ตั้งแต่ปลั๊กอินระดับพื้นฐานไป จนถึงปลั๊กอินเฉพาะทางที่ทำงานในลักษณะเฉพาะด้าน 

ปลั๊กอิน WordPress ทำหน้าที่เสมือน “เครื่องมือเสริม” ที่ช่วยเพิ่มฟังก์ชันใหม่ ๆ ให้กับ เว็บไซต์ เช่น การเพิ่มฟอร์มติดต่อ, ระบบร้านค้าออนไลน์, ระบบ SEO, หรือเครื่องมือรักษาความ ปลอดภัย เปรียบเสมือนกับการตกแต่งบ้าน ซึ่ง WordPress คือ “บ้าน” ส่วนปลั๊กอินก็เหมือนของ ตกแต่งหรืออุปกรณ์ที่ช่วยทำให้บ้านนั้นดูสวยงามและปลอดภัยมากยิ่งขึ้น 

ปลั๊กอิน WordPress มีทั้งแบบฟรีและแบบพรีเมียมให้ผู้ใช้เลือกใช้ หากต้องการฟีเจอร์ที่ครบ ครันและมีประสิทธิภาพสูง แนะนำให้ใช้ปลั๊กอินพรีเมียม แต่ในกรณีที่ปลั๊กอินที่มีอยู่ไม่ตอบโจทย์ตาม ความต้องการ ผู้ใช้สามารถพัฒนาปลั๊กอินขึ้นมาเอง หรือจ้างนักพัฒนาที่มีความเชี่ยวชาญในการเขียน ปลั๊กอิน WordPress โดยปลั๊กอินส่วนใหญ่จะถูกเขียนด้วยภาษา PHP ซึ่งเป็นภาษาหลักที่ WordPress พัฒนาขึ้น 

WordPress ยังมีระบบจัดการเนื้อหาหรือที่เรียกว่า “ระบบหลังบ้าน (Dashboard)” ซึ่งช่วย ให้ผู้ใช้สามารถสร้างและจัดการข้อมูลบนเว็บไซต์ได้อย่างง่ายดายผ่านอินเทอร์เน็ต โดยไม่จำเป็นต้อง ดาวน์โหลดโปรแกรมมาติดตั้งในเครื่อง และไม่ต้องเขียนโค้ดเอง ทำให้ผู้ใช้ทุกระดับสามารถบริหาร จัดการเว็บไซต์ได้สะดวกและรวดเร็ว 

###### **2.3 Astra Theme** 

Astra เป็นธีม WordPress ที่ได้รับความนิยมสูงในกลุ่มผู้พัฒนาเว็บไซต์และนักออกแบบเว็บ เนื่องจากมีจุดเด่นหลายประการที่ช่วยตอบโจทย์ทั้งด้านประสิทธิภาพและความง่ายในการใช้งาน จุดเด่นสำคัญของ Astra Pro คือความเร็วในการโหลดหน้าเว็บที่รวดเร็วมาก ซึ่งส่งผลโดยตรงต่อ ประสบการณ์ผู้ใช้ (User Experience หรือ UX) และการจัดอันดับในเครื่องมือค้นหา (SEO) 

#### JP ASTRA 

7 

###### **2.3.4 การรองรับ SEO และโครงสร้างที่ถูกต้อง** 

Astra Pro ถูกออกแบบให้รองรับการทำ SEO อย่างครบถ้วน โครงสร้าง HTML ของธีมถูกเขียนตาม มาตรฐานเว็บ ทำให้เครื่องมือค้นหา เช่น Google สามารถอ่านและเข้าใจเนื้อหาได้ง่ายขึ้น นอกจากนี้ Astra Pro ยังรองรับ Schema Markup ที่ช่วยเพิ่มข้อมูลโครงสร้าง (Structured Data) ให้กับ เว็บไซต์ ทำให้การแสดงผลในหน้าผลการค้นหามีความน่าสนใจและเพิ่มโอกาสในการคลิกเข้าเยี่ยมชม เว็บไซต์มากขึ้น 

###### **2.3.5 การรองรับ Responsive Design** 

Astra Pro ออกแบบมาให้รองรับการแสดงผลบนอุปกรณ์ทุกชนิดอย่างเต็มรูปแบบ ไม่ว่าจะ เป็นคอมพิวเตอร์ตั้งโต๊ะ, แท็บเล็ต หรือสมาร์ทโฟน โดยธีมจะปรับขนาดและองค์ประกอบต่าง ๆ ให้ เหมาะสมกับหน้าจออุปกรณ์นั้น ๆ อัตโนมัติ ส่งผลให้ผู้ใช้งานได้รับประสบการณ์ที่ดีไม่ว่าจะใช้อุปกรณ์ ใดในการเข้าชมเว็บไซต์ 

###### **2.4 Beaver Builder** 

Beaver Builder เป็นปลั๊กอินประเภท Page Builder ที่ได้รับความนิยมในกลุ่มผู้พัฒนาและ เจ้าของเว็บไซต์ WordPress โดยช่วยให้ผู้ใช้สามารถออกแบบและสร้างหน้าเว็บได้อย่างง่ายดายผ่าน ระบบลากและวาง (Drag and Drop) โดยไม่ต้องเขียนโค้ด 

Beaver Builder มีอินเทอร์เฟซที่ใช้งานง่าย ผู้ใช้สามารถจัดวางองค์ประกอบต่าง ๆ เช่น ข้อความ รูปภาพ วิดีโอ และวิดเจ็ต ได้ตามต้องการ พร้อมดูผลลัพธ์แบบเรียลไทม์ในขณะออกแบบ อีกทั้งยังรองรับการทำงานร่วมกับธีม WordPress หลากหลายรูปแบบ 

ปลั๊กอินนี้มีทั้งเวอร์ชันฟรีและพรีเมียม โดยเวอร์ชันพรีเมียมมีฟีเจอร์เสริม เช่น เทมเพลตหน้า เว็บสำเร็จรูป โมดูลสำเร็จรูปสำหรับฟังก์ชันต่าง ๆ ระบบจัดการสิทธิ์ผู้ใช้ และรองรับ WooCommerce สำหรับสร้างเว็บไซต์ร้านค้าออนไลน์ 

Beaver Builder ถูกพัฒนาโดยใช้ภาษา PHP และ JavaScript ทำงานร่วมกับระบบ WordPress ได้อย่างมีประสิทธิภาพ พร้อมช่วยให้เว็บไซต์มีโครงสร้างโค้ดที่สะอาดและเป็นมาตรฐาน ซึ่งส่งผลดีต่อ SEO และประสิทธิภาพการโหลดเว็บไซต์ 

##### beaverbuilder 

9 

ประสิทธิภาพ อีกทั้งยังช่วยให้โครงสร้างเว็บไซต์เป็นระเบียบ ส่งผลดีต่อ SEO และประสบการณ์ของ ผู้ใช้งาน (UX) 

###### **2.5.1 หลักการทำงานของ Sidebar ใน WordPress** 

ในระบบ WordPress พื้นที่ Sidebar คือบริเวณที่สามารถเพิ่มเนื้อหาเพิ่มเติมผ่านวิดเจ็ต (Widgets) โดยปกติ Sidebar จะอยู่ในตำแหน่งด้านข้างของหน้าเว็บไซต์ เช่น ด้านซ้ายหรือด้านขวา ขึ้นอยู่กับการออกแบบของธีม 

วิดเจ็ตใน Sidebar อาจเป็นองค์ประกอบที่มีประโยชน์ต่าง ๆ เช่น เมนูนำทาง (Navigation Menu), รายการบทความล่าสุด, ปฏิทิน, ป้ายกำกับ (Tags), ฟอร์มค้นหา, ฟอร์มติดต่อ หรือแม้กระทั่ง โค้ด HTML/JavaScript ที่เขียนขึ้นเองเพื่อแสดงโฆษณา 

โดยปกติ Sidebar จะถูกกำหนดไว้ล่วงหน้าในไฟล์ธีม (Theme Template) และมีเพียงไม่กี่ ชุดให้เลือกใช้งาน ดังนั้นหากต้องการให้แต่ละหน้าแสดง Sidebar แตกต่างกัน ผู้ใช้จะต้องแก้ไข โค้ดธีมหรือใช้ปลั๊กอินเสริมอย่าง Custom Sidebars 

###### **2.5.2 คุณสมบัติของปลั๊กอิน Custom Sidebars** 

ปลั๊กอิน Custom Sidebars มีจุดเด่นหลายประการที่ช่วยเพิ่มประสิทธิภาพในการออกแบบ และบริหารจัดการแถบด้านข้างของเว็บไซต์ WordPress ได้แก่: 

- **สร้าง Sidebar ได้ไม่จำกัด:** ผู้ใช้สามารถสร้างแถบด้านข้างได้หลายชุดตามต้องการ โดยไม่มี ข้อจำกัด 

- **กำหนดเงื่อนไขการแสดงผลแบบเฉพาะเจาะจง:** เช่น ให้ Sidebar ชุดหนึ่งแสดงเฉพาะใน หน้าบทความหมวด "ข่าวสาร" และอีกชุดหนึ่งในหน้าสินค้า 

- **ลากและวางวิดเจ็ตได้สะดวก:** ใช้งานง่ายผ่านระบบหลังบ้านของ WordPress โดยไม่ต้องมี ความรู้ด้านการเขียนโค้ด 

- **รองรับธีมและปลั๊กอินยอดนิยม:** สามารถใช้งานร่วมกับธีมต่าง ๆ และ Page Builder เช่น Astra, GeneratePress, Elementor, Beaver Builder ได้เป็นอย่างดี 

- **การจัดการแบบ Visual และเรียลไทม์:** แสดงผลการเปลี่ยนแปลงได้ทันที ทำให้การ ปรับแต่งสะดวกและแม่นยำมากขึ้น 

###### **2.5.3 ประโยชน์ของการใช้ Custom Sidebars** 

การใช้ Custom Sidebars ในเว็บไซต์มีประโยชน์ต่อการจัดโครงสร้างเว็บไซต์ การออกแบบ UX/UI และการทำการตลาด ดังนี้: 

- ปรับแต่งเนื้อหาตามบริบท: แสดงเนื้อหาที่เหมาะสมในแต่ละหน้า ช่วยให้ผู้ใช้งานได้รับ ประสบการณ์ที่ดีและเกี่ยวข้องกับสิ่งที่กำลังสนใจ 

10 

- **เพิ่มการมีส่วนร่วมของผู้ใช้งาน:** การวางลิงก์ที่เกี่ยวข้อง ฟอร์มสมัคร หรือปุ่ม Call-toAction บริเวณ Sidebar จะช่วยให้ผู้ใช้มีโอกาสคลิกและดำเนินการตามเป้าหมายของ เว็บไซต์ 

- **ส่งเสริมกลยุทธ์ SEO:** การจัดวางเนื้อหาอย่างมีประสิทธิภาพ ช่วยให้เครื่องมือค้นหาเข้าใจ โครงสร้างเว็บไซต์ได้ง่ายขึ้น 

- **ไม่ต้องเขียนโค้ด:** เหมาะสำหรับผู้ที่ไม่มีพื้นฐานการพัฒนาเว็บไซต์ แต่ต้องการควบคุมการ แสดงผลของ Sidebar อย่างยืดหยุ่น 

- **ประหยัดเวลาและลดความซับซ้อน:** สามารถจัดการทุกอย่างผ่านหน้า Dashboard โดยไม่ ต้องยุ่งกับโค้ดหรือแก้ไขธีมโดยตรง 

###### **2.6 LoginPress** 

LoginPress เป็นปลั๊กอินสำหรับระบบ WordPress ที่ใช้ในการปรับแต่งหน้าเข้าสู่ระบบ (Login Page) โดยเฉพาะ ซึ่งเหมาะสำหรับเว็บไซต์ที่ต้องการความเป็นเอกลักษณ์ (Branding) หรือ ประสบการณ์ใช้งานที่แตกต่างจากหน้า Login พื้นฐานของ WordPress โดยไม่ต้องเขียนโค้ดด้วย ตนเอง 

ปลั๊กอิน LoginPress มาพร้อมอินเทอร์เฟซที่ใช้งานง่าย รองรับการปรับแต่งแบบเรียลไทม์ (Live Preview) ผ่าน Customizer ของ WordPress ซึ่งทำให้ผู้ใช้งานสามารถออกแบบและดูผลลัพธ์ ได้ทันที โดยไม่ต้องรีเฟรชหน้าเว็บหรือเข้าสู่ระบบใหม่ซ้ำหลายครั้ง 

###### **2.6.1 คุณสมบัติหลักของ LoginPress** 

LoginPress มีฟีเจอร์ที่หลากหลายและตอบโจทย์การใช้งานทั้งในด้านการตกแต่ง ความ ปลอดภัย และประสบการณ์ของผู้ใช้งาน ดังนี้: 

- **ปรับแต่งโลโก้หน้า Login:** ผู้ดูแลระบบสามารถเปลี่ยนโลโก้ WordPress ให้เป็นโลโก้ของ เว็บไซต์หรือองค์กรได้ 

- **เปลี่ยนพื้นหลัง:** รองรับการตั้งค่าพื้นหลังแบบภาพนิ่ง, พื้นหลังแบบสีไล่เฉด (gradient) หรือ แม้กระทั่งพื้นหลังแบบวิดีโอ (ในเวอร์ชันพรีเมียม) 

- **เปลี่ยนสีและฟอนต์ของข้อความต่าง ๆ:** เช่น ป้ายฟอร์ม, ปุ่มเข้าสู่ระบบ, ลิงก์ “ลืม รหัสผ่าน” เป็นต้น 

- **แสดงข้อความแจ้งเตือนแบบกำหนดเอง (Custom Error Messages):** เพื่อให้ผู้ใช้งาน เข้าใจง่ายขึ้นเมื่อล็อกอินผิดพลาด 

- **กำหนดลิงก์ “เข้าสู่ระบบ” และ “ออกจากระบบ” ได้:** เพิ่มความยืดหยุ่นในการกำหนด พฤติกรรมของระบบ 

11 

- **Live Preview Customizer:** สามารถเห็นผลลัพธ์ทันทีขณะออกแบบ 

- ฟีเจอร์เสริมในเวอร์ชัน Pro: 

   - Google reCAPTCHA 

   - Social Login (เช่น Facebook, Google) 

   - ล็อกอินแบบสองขั้นตอน (Two-Factor Authentication) 

   - Redirect หลังล็อกอินแยกตามบทบาทผู้ใช้ (User Role) 

###### **2.6.2 ประโยชน์ของการใช้ LoginPress** 

การติดตั้งและใช้งาน LoginPress ช่วยให้เว็บไซต์ WordPress มีความเป็นมืออาชีพมากขึ้น 

ทั้งในแง่ของภาพลักษณ์และความปลอดภัย: 

- **สร้างภาพลักษณ์ที่ดีต่อผู้ใช้งาน (Branding):** หน้าเข้าสู่ระบบสามารถตกแต่งให้สอดคล้อง กับธีมหรือเอกลักษณ์ขององค์กร 

- **ประสบการณ์ผู้ใช้ที่ดีขึ้น:** การออกแบบที่สวยงาม สะอาด และเข้าใจง่ายจะช่วยให้ผู้ใช้งาน รู้สึกมั่นใจ 

- **เพิ่มความปลอดภัย:** การใช้ฟีเจอร์อย่าง reCAPTCHA, ปิด URL ของ wp-login, หรือ ตรวจจับ IP ที่เข้าสู่ระบบผิดพลาดหลายครั้ง สามารถลดโอกาสการถูกโจมตีแบบ Brute Force ได้ 

- **ง่ายต่อการใช้งาน:** ผู้ดูแลระบบไม่จำเป็นต้องเขียนโค้ด HTML/CSS เอง สามารถออกแบบ ผ่านเครื่องมือที่ใช้งานง่าย 

- **เหมาะกับเว็บไซต์ที่มีหลายบทบาทผู้ใช้:** เช่น เว็บไซต์สมาชิก, เว็บไซต์ LMS (ระบบเรียน ออนไลน์), หรือเว็บไซต์ชุมชน (Community Website) 

###### **2.7 Visual Studio Code** 

Visual Studio Code (VS Code) คือโปรแกรมแก้ไขข้อความหรือโค้ด (Source Code Editor) แบบโอเพ่นซอร์สที่ได้รับความนิยมในกลุ่มนักพัฒนาเว็บไซต์และซอฟต์แวร์อย่างแพร่หลาย พัฒนาโดย Microsoft โดยมีจุดเด่นด้านความเร็ว ประสิทธิภาพ ความสามารถในการขยายฟีเจอร์ และอินเทอร์เฟซที่ใช้งานง่าย รองรับระบบปฏิบัติการทั้ง Windows, macOS และ Linux 

**2.7.1 คุณสมบัติเด่นของ Visual Studio Code** 

- 2.7.1.1 รองรับหลายภาษา (Multilanguage Support) 

รองรับภาษาโปรแกรมยอดนิยม เช่น HTML, CSS, JavaScript, PHP, Python, SQL ฯลฯ 

พร้อมระบบ IntelliSense ที่ช่วยแนะนำคำสั่งอัตโนมัติ ทำให้เขียนโค้ดได้รวดเร็วและแม่นยำขึ้น 

- 2.7.1.2 เทอร์มินัลในตัว (Integrated Terminal) 

12 

สามารถใช้งานเทอร์มินัล (Terminal) ภายในตัวโปรแกรมได้ทันที โดยไม่ต้องเปิดโปรแกรม อื่น เพิ่มความสะดวกในการใช้งานคำสั่งต่าง ๆ เช่น git, composer, npm ฯลฯ 

2.7.1.3 ระบบควบคุมเวอร์ชัน Git 

มีระบบควบคุมเวอร์ชัน (Version Control) ที่ผสานรวมกับ Git ช่วยให้นักพัฒนาสามารถ commit, push, pull หรือ merge โค้ดได้จากในโปรแกรม 

- 2.7.1.4 ส่วนขยาย Live Server 

สามารถเรียกดูหน้าเว็บไซต์แบบเรียลไทม์ในเบราว์เซอร์ได้ทันทีเมื่อมีการแก้ไขและบันทึกไฟล์ เหมาะสำหรับงานออกแบบและพัฒนาเว็บไซต์ 

2.7.1.5 เครื่องมือ Debug ในตัว 

สามารถตรวจสอบข้อผิดพลาดของโค้ดและแก้ไขได้ทันทีภายใน VS Code โดยเฉพาะใน ภาษา JavaScript, TypeScript และ Node.js 

2.7.1.6 ปรับแต่งหน้าตาและฟังก์ชันเพิ่มเติม 

สามารถปรับแต่งหน้าตา Editor และติดตั้ง Extensions เพิ่มเติมได้ตามต้องการ เช่น Themes, Code Formatter, Auto Rename Tag ฯลฯ 

**2.7.2 ประโยชน์ของ Visual Studio Code ในการพัฒนาเว็บไซต์** 

Visual Studio Code เป็นเครื่องมือที่เหมาะสำหรับนักพัฒนาเว็บไซต์ทุกระดับ ตั้งแต่ผู้ เริ่มต้นจนถึงมืออาชีพ เนื่องจากมีความสามารถที่ตอบโจทย์ด้านการพัฒนาเว็บได้ครอบคลุม เช่น 

- ประหยัดเวลาในการเขียนโค้ด ด้วยระบบ IntelliSense และ Snippet 

- รองรับการทำงานแบบทีม ผ่าน Git และระบบ Remote Repositories 

- เหมาะกับ Frontend และ Backend รองรับทั้ง JavaScript Framework และภาษา Server-side 

- ใช้งานฟรี และสามารถติดตั้งส่วนขยายเสริมได้มากมายจาก Marketplace 

- ทำงานได้ข้ามแพลตฟอร์ม ทั้ง Windows, macOS และ Linux 

**2.7.3 เทคโนโลยีที่ใช้ในการพัฒนา Visual Studio Code** 

VS Code ถูกพัฒนาขึ้นโดยใช้เทคโนโลยีหลักดังนี้: 

- **Electron Framework:** เป็นเฟรมเวิร์กที่อนุญาตให้พัฒนาแอปพลิเคชันแบบเดสก์ท็อปด้วย 

- HTML, CSS และ JavaScript 

- **Node.js:** ใช้เป็น runtime สำหรับการรัน JavaScript ฝั่งเซิร์ฟเวอร์ภายในแอป 

- **TypeScript:** เป็นภาษาที่พัฒนาต่อยอดจาก JavaScript เพิ่มความสามารถในการ ตรวจสอบชนิดข้อมูล (Static Type Checking) 

###### Visual Studio Code 



<!-- Start of picture text -->
°<br><!-- End of picture text -->

# . Figma 

16 

###### **2.10.1 คุณสมบัติหลักของ phpMyAdmin** 

phpMyAdmin มีฟังก์ชันครบถ้วนสำหรับการจัดการฐานข้อมูล เช่น: 

- สร้างและลบฐานข้อมูล: สามารถสร้างฐานข้อมูลใหม่ หรือลบฐานข้อมูลที่ไม่ต้องการได้อย่าง ง่ายดาย 

- สร้าง/แก้ไขตารางข้อมูล (Tables): รองรับการกำหนดชนิดข้อมูล (Data Type), คีย์หลัก (Primary Key), คีย์ต่างประเทศ (Foreign Key) 

- ดูและแก้ไขข้อมูล: สามารถเพิ่ม ลบ แก้ไข แถวข้อมูลได้จากหน้าจอโดยตรง 

- เขียนและรันคำสั่ง SQL: รองรับการรันคำสั่ง SQL ด้วยตนเองสำหรับงานที่ซับซ้อน 

- นำเข้า/ส่งออกข้อมูล: สามารถนำเข้าฐานข้อมูลจากไฟล์ .sql, .csv และสามารถส่งออกใน หลายรูปแบบ เช่น SQL, CSV, PDF 

- สำรองและกู้คืนข้อมูล: มีระบบ Backup ที่ช่วยให้ผู้ใช้สามารถสำรองข้อมูลหรือเรียกคืน ข้อมูลเมื่อเกิดข้อผิดพลาด 

- จัดการสิทธิ์ผู้ใช้: สามารถเพิ่มผู้ใช้ กำหนดรหัสผ่าน และจำกัดสิทธิ์การเข้าถึงของผู้ใช้งานแต่ ละคน 

###### **2.10.2 การทำงานของ phpMyAdmin** 

phpMyAdmin ทำงานผ่านเว็บเซิร์ฟเวอร์ (เช่น Apache) โดยเป็นตัวกลางระหว่างผู้ใช้กับระบบ ฐานข้อมูล MySQL/MariaDB ผ่านการส่งคำสั่ง SQL ที่สร้างขึ้นโดยอัตโนมัติผ่านการคลิกเมนูต่าง ๆ ของผู้ใช้ ทำให้การใช้งานเป็นมิตรกับผู้เริ่มต้น และช่วยลดความผิดพลาดจากการเขียนคำสั่งด้วยมือ โดยทั่วไป phpMyAdmin มักติดตั้งมาพร้อมกับเครื่องมือพัฒนาเว็บเช่น XAMPP, WAMP, หรือ MAMP ซึ่งช่วยให้ใช้งานได้ทันทีโดยไม่ต้องติดตั้งแยก 

###### **2.10.3 ประโยชน์ของการใช้ phpMyAdmin** 

- ใช้งานง่าย: อินเทอร์เฟซแบบกราฟิกเหมาะกับทั้งมือใหม่และมืออาชีพ 

- ไม่ต้องใช้คําสั่งมาก: ลดความจำเป็นในการใช้คำสั่ง SQL สำหรับงานพื้นฐาน 

- เข้าถึงได้จากทุกที่: หากติดตั้งบนเซิร์ฟเวอร์ออนไลน์ สามารถเข้าจัดการฐานข้อมูลจากที่ใดก็ ได้ 

- เหมาะกับระบบพัฒนาเว็บไซต์: โดยเฉพาะระบบ CMS อย่าง WordPress, Joomla หรือ Drupal ที่ใช้ MySQL เป็นฐานข้อมูลหลัก 

- ประหยัดเวลา: เพิ่มประสิทธิภาพการทำงานของนักพัฒนาเว็บไซต์หรือระบบข้อมูล 

###### phpMyAdmin 

18 

- คลาสยูทิลิตี้ (Utility Classes): Bootstrap มีคลาสสำหรับจัดการระยะขอบ สี ตัวอักษร การ จัดตำแหน่ง ฯลฯ ช่วยให้การปรับแต่งเว็บทำได้โดยไม่ต้องเขียน CSS เพิ่ม 

- รองรับ JavaScript Plugins: มีปลั๊กอิน JavaScript ที่ช่วยเสริมฟังก์ชันการทำงานของ เว็บไซต์ เช่น Modal, Carousel, Tooltip, Collapse โดยสามารถใช้งานได้ทันที 

- สามารถปรับแต่งได้ (Customizable): Bootstrap มีตัวแปร SCSS และระบบ Theme ที่ ช่วยให้นักพัฒนาสามารถออกแบบเว็บไซต์ให้ตรงตามภาพลักษณ์ขององค์กรหรือแบรนด์ 

###### **2.11.2 ประโยชน์ของการใช้ Bootstrap** 

การนำ Bootstrap มาใช้ในการพัฒนาเว็บไซต์มีข้อดีหลายประการ ดังนี้ 

- ช่วยลดระยะเวลาในการพัฒนาเว็บไซต์: เนื่องจากมีคอมโพเนนต์และเลย์เอาต์พื้นฐานพร้อม ใช้งาน 

- เหมาะกับผู้ใช้งานทุกระดับ: ทั้งนักพัฒนาเริ่มต้นและมืออาชีพสามารถใช้งาน Bootstrap ได้ อย่างรวดเร็ว 

- รองรับทุกเบราว์เซอร์หลัก: เช่น Google Chrome, Mozilla Firefox, Safari, Microsoft Edge เป็นต้น 

- ทำให้เว็บไซต์มีมาตรฐานและทันสมัย: ด้วยการออกแบบที่สวยงาม มีความเป็นมืออาชีพ และ รองรับการใช้งานบนอุปกรณ์พกพา 

- มีชุมชนผู้ใช้งานและเอกสารอ้างอิงจำนวนมาก: ซึ่งช่วยในการแก้ไขปัญหาและเพิ่ม ประสิทธิภาพในการเรียนรู้ 

###### **2.11.3 เวอร์ชันล่าสุดและการใช้งาน** 

ในปัจจุบัน Bootstrap ได้พัฒนามาถึงเวอร์ชัน 5 ซึ่งมีการปรับปรุงครั้งใหญ่หลายประการ โดยเฉพาะการตัด jQuery ออกจากระบบ ทำให้ Bootstrap มีขนาดเล็กลง และโหลดได้เร็วขึ้น รวมถึงเพิ่มฟีเจอร์ใหม่ ๆ ที่สำคัญ เช่น 

- ปรับปรุงระบบ Grid Layout ให้ยืดหยุ่นมากยิ่งขึ้น 

- รองรับระบบยูทิลิตี้แบบกำหนดเอง (Utility API) 

- ปรับปรุงระบบการเข้าถึง (Accessibility) ให้ดีขึ้น 

- เพิ่มฟีเจอร์สำหรับสร้าง Dark Mode ได้ง่ายขึ้น 





<!-- Start of picture text -->
=<br><!-- End of picture text -->



<!-- Start of picture text -->
5)<br><!-- End of picture text -->



<!-- Start of picture text -->
°<br><!-- End of picture text -->





























<!-- Start of picture text -->
~ View Details ~ View Details ~ View Details ~ View Details<br>@ Receive (J Return @ Available U Delete<br>\. Maintenance \. Maintenance @ Retired @ Available<br>@ Retired @ Retired 0 Delete @ Edit<br>0 Delete 0 Delete ®@ Edit<br>® Edit ‘® Edit<br><!-- End of picture text -->



<!-- Start of picture text -->
V=E\\<br>Hin)<br>aN pm IT<br><!-- End of picture text -->



<!-- Start of picture text -->
||<br>|| |<br>| avdiauadaniu . | |<br>,<br>|||<br>|| eg |<br>| asadautauaaanau |<br>|||<br>||. Guna (ausa/aaunar) |<br>| (a- ~~ rrr nnn<br>lo. « -. | |<br>leg WAVAMUZAANdY | |<br>|||<br>|| |<br>|||<br><!-- End of picture text -->



<!-- Start of picture text -->
A=<br>ei tai DB<br>||<br>|| |<br>| Request CRUD wine _ | |<br>-— > |<br>| | sudunisvayaninswy |<br>|||<br>||. fiudumanissuiiuns |<br>| ais<br>|-||<br>|Ldaonaaws | |<br>|||<br>|| |<br>| | |<br><!-- End of picture text -->



<!-- Start of picture text -->
: wih Add Device w<br>ella DB<br>||<br>|| |<br>| Request CRUD ainsai . | |<br>| >| |<br>: autunistiauaaunsai .<br>|||<br>|| duduna |<br>| eeeae Sarees<br>Ldounaawns | |<br>|||<br>|| |<br>| | |<br><!-- End of picture text -->



<!-- Start of picture text -->
: uindauaaunsai a<br>ei tai ~ DB<br>||<br>|| |<br>| Savvatinadnsai | | |<br>|||<br>, || aszadauamuszaunsai- .<br>|||<br>|| Uuviniiauatis-Au |<br>||><br>|| fudunisiurin ,<br>| geil| gn |<br>| | ayannuz In Use |<br>| sansSe Seat<br>Lg WavNANIsiN-AU | |<br>|||<br>|| |<br>| | |<br><!-- End of picture text -->



<!-- Start of picture text -->
} ui Maintenance 7<br>ei t2i DB<br>||<br>|| |<br>| anvéauaihivinn | | |<br>><br>||<br>|¥ - | |<br>nsaniauaaunsai |<br>|||<br>|| duvindauaissinw , |<br>|><br>|| 7 ra ~~ <a |<br>| ld duduniuuvin J<br>|||<br>|<br>1 |<br>\< udviiududrya | |<br>|||<br>|| |<br><!-- End of picture text -->



<!-- Start of picture text -->
: win winiauadita w<br>ei tai ~~ DB<br>||<br>|| |<br>| atwiunla/audta | | |<br>| >| |<br>! | aTIAdAUAANAUALA UhUNNS |<br>|||<br>|| fuduna |<br>| (4-- = rn nnn nnn<br>| . ra ~ = | |<br>| LAINANITAANITUEAUA | |<br>|||<br>|| |<br>| | |<br><!-- End of picture text -->



<!-- Start of picture text -->
} vin Histo =<br>titel y DB<br>||<br>|| |<br>| Auwissaanistae | | |<br>| > |<br>asdauausIA |<br>|||<br>||. dvtiauaussia |<br>| pap Sas ae<br>l udnviaualss3A |<br>|||<br>|| |<br>| | |<br><!-- End of picture text -->



<!-- Start of picture text -->
k uin View Details w<br>eitai DB<br>||<br>|| |<br>| Aunriaua | |<br>[ >| |<br>| | dotiauanianisaunsainnd tae wliaem<br>|||<br>|| |[4 nnnaviiauasinis nnn|<br>||udaymunisaunsai.|| ||<br>|||<br>|| |<br>| | |<br><!-- End of picture text -->



<!-- Start of picture text -->
| os<br>[cao [oan<br>CategoryName user_email<br>user_email CreatedAt 1 | ___ statuses<br>UpdateAt 1 1 StatusName<br>M user_email<br>. ~ UpdateAt<br>DevicelD<br>[| keywords |e] DevicelDtoca | tro M [Poston|<br>[Px] P SerialNumber , J px | Positond<br>KeywordName Action FK | StatusID P PositionName<br>user_email Date ReceiveDate 1 OwneriD<br>CreatedAt Description 1 ReturnDate OwnerType<br>UpdateAt user_email AddDeviceDate Nickname-<br>CategoryID RepairDate<br>Owner FK | OwneriD Pp M FirstNameLastname<br>FK | DepartmentiD < FK | DepartmentID<br>user_email 1<br>FK | PositionID<br>Maintenance_ M UpdateAtwv CreatedAt<br>ae =<br>DevicelD<br>RepairDate Departments<br>ba ae<br>user_email DepartmentName<br>CreatedAt CreatedAt<br>UpdateAt UpdateAt<br><!-- End of picture text -->

### Stock Supply W tos 



<!-- Start of picture text -->
Stock Supply tosmarsening ©<br>@ Dashboard<br>© Monitor<br>Dashboard<br>& Laptop<br>Accessories<br># Maintenance alvailable In Use ) r n- K4 RetiredJ TyMY)<br>'D History<br>& Employees 37 Units 63 Units 3 Units<br>34% 57% 6% . 3%<br>+ Add Device :<br>© Logout All Devices All Monitor All Laptop All Accessories<br>42 Unit 28% Unit 30% Unit<br>no ») 46 ms’ 31 my) 33<br>1D Category Brand Model Serial No. Owner Department Status _—Receive Date Return Date Repair Date Action<br>ACCO01 Accessories Logitech K120 194MR173A08 Pearchan S. (Intern) T @inuse 2025-07-08 - - fal<br>ACCO02 Accessories Logitech K120 2204MR233A38 Bank S. (Intern) T @inuse 2025-07-07 &<br>ACCO03 Accessories Logitech Migs 2122LZXDEBRB Bank S. (Intern) iT @inuse 2025-07-08 ‘ : f<br>ACCOO4 Accessories Lenovo ADLX6SYLC3D 8SSA27FT @ Available - F)<br>ACCOOS Accessories Logitech B100 2445XKTB - - @ Available - - - A<br>ACCO06ACCO07 AccessoriesAccessories DellDell HKA6SNM201K6216 CNO62VYNLO3008ATOJOTAO3CNOBJAGO -- =- @@ AvailableAvailable ss- =- =- *].<br>ACCO08 Accessories HP MODGUO FCVRVOAHDS98V! - : @ Available - - - g<br>ACCO09 Accessories Logitech M100r 1748HS037TAB - - @ Available = 2 =<br>ACCO10 Accessories Logitech B100 2AASAPVOXKSB - - @ Available - - - f<br>ACCO11 Accessories INPHIC PM6BS - - - @ Retired - - - g<br>ACCO12 Accessories Logitech K120 2204MR233A18 Satang (Full-Time) iT @inuse 2025-07-09 - - [2<br>ACCO13 Accessories Acer Aspire MC60S_-- DTSM1ST031332058993000 - - @ Available - - - f ><br>ACCO14 Accessories HP HP 8300 SFF SGH331ROP9 - - © Available - - - r<br>ACCO1S Accessories Logitech 8100 23291020038 Park F, (Full-Time) T @inuse 2025-07-09 - - a<br>ACCO16 Accessories Logitech K120 2204MR233A48 Park F, (Full-Time) T @inuse 2025-07-10 - - A<br>ACCO17 Accessories Zelotes 1-30 3020240315189 Mummy N. (Full-Time) i @inUse 2025-07-13 - - &<br>‘ACCO18 Accessories INPHIC PM6BS - - - @ Available - - - R<br>ACCO19 Accessories HXSJ 6D Optical Mouse - - - @ Maintenance - - 2025-07-04<br>ACCO20 Accessories Logitech 6304 - - - @ Available - - -<br>ACCO21 Accessories INPHIC P-M6 - - - @ Available - - - ;<br>ACCO22 Accessories OKER KM-6120 - - - @ Available - - -<br>ACCO23 Accessories INPHIC PMeBS - - - @ Available - - - .<br>ACCO24 Accessories INPHIC P-M6 - - - @ Retired - - -<br>ACCO25 Accessories INPHIC PMeBS - - - @ Available - - -<br>Previous + | 2.) 3 [By S| (Next<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmarkerina ©<br>Dashboard<br>© Monitor<br>Monitor<br>& Laptop<br>%& Accessories<br># Maintenance Available In Use SS) tenane SK Retired iRY<br>SD History<br>15 Units 30 Units 0 Unit<br>© Employees — 14% — 27% 1% 0%<br>F Add Device<br>© Logout All Monitor<br>:“) 46Unit<br>Device Search Device. (Search)<br>ID Category Brand Model Serial No. Owner Department Status Receive Date Return Date Repair Date Action<br>MONO046 Monitor AOC 24G2SE YUPSHA000496 Megan (Intern) SEO @ in Use 2025-07-04 = Bh<br>MONO045 Monitor Samsung ‘S24F350FHE ZZNPH4Z)301199A Winner (Full-Time) SEO @ in Use 2025-07-04 - - j<br>MON044 Monitor Dell E1911 CN-OCGJ4M-64180-23F-02CS- Ploy (Intern) SEM @ in Use 2025-07-02 = «<br>MON043 Monitor Acer G23SH ETLK60C0231030402F4012 Pemai (Full-Time) ‘SEM @ in Use 2025-07-02 - «<br>MONO42 Monitor Asus ‘VE278 DILMTFO36611 Non (Full-Time) Graphic @ in Use 2025-07-04<br>MON041 Monitor BenQ GL2760-8 ET88F02664SLO Non (Full-Time) Graphic @ In Use 2025-07-03 = = ;<br>MONO40MONO039 MonitorMonitor BenQLG 24MK430HGL2760-B ET88F0274SSLOTO4INTXOW137 PimPim (Full-Time)(Full-Time) GraphicGraphic @@ inusein Use 2025-07-022025-07-03 = 2 .<br>MONO38 Monitor Acer VG240Y MMTFSS00193300C102400 = Amber (Full-Time) ‘SEM @ inUse 2025-07-02<br>MON037 Monitor Acer ‘K242HL MMT1MSS0046 19069354200 Golf (Full-Time) ‘SEM @ in Use 2025-07-04 ad = a<br>MONO36 Monitor Asus PB278 ESLMTFO96610 Kowkong (Full-Time) — Graphic @ inuse 2025-07-02 - - f<br>MONO35_ Monitor LG 27MP25HQ-B 40SINUBSK214 Kowkong (Full-Time) Graphic @ in Use 2025-07-02 - .<br>MONO34 Monitor Samsung S24R35AFHE SDNCH4TTBO0351D - - @ Available - - -<br>MONO033_ Monitor Dell P2214Hb (CN-OCY84D-74261-46D-1G2B Surf (Intern) SEO @ in Use 2025-07-13 :<br>MONO32_ Monitor Dell U2212HMc (CN-OGCCD2-64180-252-09LL First (Full-Time) Content @ inUse 2025-07-12 - -<br>MONO31 Monitor Acer ‘S230HL MMLTSSS00332208A812401 Ruj (Full-Time) SEO @ in use 2025-07-13 +<br>MON030 Monitor Dell U2212HMc CN-OY7MSS-64180-37A-08ZL. Bam (Full-Time) Content @ inUse 2025-07-04 - - ]<br>MONO029 Monitor Acer K242HL MMTOFSSO01615012068500 - - @ Available - - - ;<br>MON028 Monitor AOC 24G2E ATNL41A020672 Toddy (Full-Time) SEO. @ in Use 2025-07-10 - - c<br>MON027 Monitor Dell U2212HMc (CN-OY7M55-64180-37A-ONHL —Noey (Full-Time) SEO @ inuse 2025-07-09 - -<br>MON026 Monitor Lenovo ThinkVision E24-10 6 1B7JAR6WWV905M559 - - @ Available - - - i<br>MONO25 Monitor Dell £203H (CN-01X2HC-64180-349-1PZM - - @ Available - - -<br>MON024 Monitor AOC 215LM00041 ANQG71A000245 Poom (Full-Time) PBN @ in Use 2025-07-12 - - |<br>MON023 Monitor Asus PB278 FOLMTF113819 Poom (Full-Time) PBN @ In Use 2025-07-09 - - f<br>MONO22 Monitor BenQ ET-0032-T £18BB07722019 - - @ Available - - - f |<br>Previous | 2 Next<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmatteniaa ©<br>Dashboard<br>© Monitor<br>Laptop<br>fl Laptop<br>& Accessories<br>Zia Mertscains Available In Use SY) K Retired Ry<br>D History<br>ay copies 6 Units 20 Units 0 Unit<br>. 5% — 18% 5% 0%<br>F Add Device<br>G Logout All Laptop<br>ID Category Brand Model Serial No. Owner Department Status Receive Date Return Date Repair Date Action<br>LAPO31 Laptop Dell Asdasdsa Dasdad Bank S. (Intern) T @ Maintenance 2025-07-04 % 2025-07-17 -E<br>LAPO30 Laptop HP Pavilion X360 SCGO348GT7 Magan (Intern) SEO @ in use 2025-07-01 - -<br>LAPO29 Laptop Lenovo Thinkpad T480S PC-OW9SJ9 Winner (Full-Time) SEO @ inuUse 2025-07-02 - - ;<br>LAPO28 Laptop Dell P102F 1Q49863 Ploy (Intern) SEM @ inuse 2025-07-03 - =<br>LAPO27 Laptop Lenovo T480s PCO9SHQ Pemai (Full-Time) SEM @ inUse 2025-07-08 E<br>LAPO26 Laptop Lenovo ThinkPad T14s Gen 1 PCINGIJES Amber (Full-Time) SEM @ in Use 2025-07-07 - - :<br>LAPO25 Laptop Huawei KLVL-WFHS SYDBB20826801592 Nerd (Full-Time) Content Writer(TH) = @ In Use 2025-07-04 if 2;<br>LAPO24 Laptop Lenovo ThinkBook 15 G2 ITL MmP200)07 Ruj (Full-Time) SEO @ in Use 2025-07-08 - -<br>LAPO23 Laptop Lenovo 81xX2 R9OZLFDH Bam (Full-Time) Content @ inuse 2025-07-13 - - 5<br>LAP022 Laptop Lenovo Thinkpad T 4805 PC-OWS5GV - - @ Available - - = :<br>LAPO21 Laptop Lenovo Thinkpad T14 Gen 2 PF-3Q3A4G - - @ Available - - 3<br>LAPO20 Laptop = Asus MS15D NINOCV180015039 First (Full-Time) Content @ in use 2025-07-06 - =<br>LAPO19 = Laptop HP ZBook 15 GS. SCD9O8OX9L Toddy (Full-Time) SEO @ inuse 2025-07-14 - - :<br>LAPO18 Laptop Lenovo ThinkBook 15 G2 ITL MP22NED1 Noey (Full-Time) SEO @ in Use 2025-07-09 - - Re<br>LAPO17 Laptop = Asus UX410U, H2NOCV14942808C - - @ Maintenance - - 2025-07-10 ;<br>LAPO16 Laptop = Acer Swift Sf314-51 NXGKLST022716000E47200 = @ Available * =<br>LAPO1S Laptop Lenovo Thinkpad X1 Yoga RSONH9VC ‘Sumo (Full-Time) SEO @ InUse 2025-07-03 - - %<br>LAPO14 = Laptop HP Pavilion X360 2-In-1 8CG22801PT March (Full-Time) Sale @ inuse 2025-07-16 bd 1“ 2<br>LAPO13 Laptop Dell Latitude 5310 2020AP1831 Bille (Full-Time) Sale @ in Use 2025-07-03 - - 3<br>LAPO12 Laptop Huawei KLVL-WFE 6TLBB20909800139 Bille (Full-Time) Sale @ inUse 2025-07-06 - = a<br>LAPO11 Laptop Dell Latitude 5421 JZL2FK3 Mummy N, (Full-Time) T @ in Use 2025-07-10 > = 3<br>LAPO10 «Laptop = Asus D3500Q M9NOCX26574739E Park F. (Full-Time) Tv @ inUse 2025-07-02 z2<br>LAPOOS Laptop Lenovo Thinkpad 14 Gen2 PF-303VLV Satang (Full-Time) T @ inUse 2025-07-16 - - =<br>LAPOOS Laptop Acer SWIFT SF314-51 |NXGKBSTO11728039177200 @ Available 2<br>LAPOO7 Laptop HP 13-AGOOQOAU 8CG9130FOX - - @ Maintenance - - 2025-07-02 2<br>Previous | 2 Next<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmarkering ©<br>Dashboard<br>© Monitor<br>Accessories<br>& Laptop<br>s& Accessories<br># Maintenance Available} In Use ) K Retired| V4XX)<br>D History<br>& Employees 16 Units 13 Units 3 Units<br>—_— 15% -_ 12% 1% © 3%<br>F Add Device<br>& Logout All Accessories<br>Unit<br>Device Search Device. -- Select Keyword -- v Search<br>ID Category Brand Model Serial No. Owner Department Status Receive Date Return Date Repair Date Action<br>ACCO033 Keyboard Logitech K270 2149SY03QKFS- Sumo (Full-Time) SEO @ in Use 2025-07-17 - -<br>ACCO32 Mouse Zelotes 1-30 13020230709189 Sumo (Full-Time) SEO @ In Use 2025-07-17 - -<br>ACCO31 Keyboard Marvo KM400 20190100183 March (Full-Time) Sale @ inuse 2025-07-17<br>ACCO30 Mouse = INPHIC PM6BS - March (Full-Time) Sale @ in Use 2025-07-16 - -<br>ACCO29 Keyboard Logitech K120 2305MR13AAS8 Bille (Full-Time) Sale @ in Use 2025-07-16<br>ACCO28 Mouse HXS) T24 - Bille (Full-Time) Sale @ in Use 2025-07-16 - -<br>ACCO27 Keyboard Logitech K120 2216MR189CA8 - @ Available<br>ACCO26 Mouse —Primaxx KM-511 - - - @ Retired - - -<br>ACCO25. Mouse INPHIC PM6BS - - - @ Available - - -<br>ACCO24 Mouse = INPHIC P-M6 - - - @ Retired - - -<br>ACCO23. Mouse — INPHIC PM6BS - - - @ Available - - -<br>ACCO22 Mouse = OKER KM-6120 - - - @ Available - - -<br>ACCO21. Mouse = INPHIC P-M6 - - - @ Available - - -<br>ACCO20 Mouse Logitech G304 @ Available<br>ACCO19. Mouse HXS) 6D Optical Mouse - - - @ Maintenance - - 2025-07-04<br>ACCO18 =Mouse = INPHIC PM6BS - - @ Available - -<br>ACCO17_ “Mouse = Zelotes T-30 73020240315189 Mummy N. (Full-Time) Tv @ in Use 2025-07-13 - -<br>ACCO16 Keyboard Logitech K120 2204MR233A48 Park F. (Full-Time) TT @ InUse 2025-07-10 - -<br>ACCO1S Mouse Logitech B100 2329H02D038 Park F, (Full-Time) T @ in Use 2025-07-09 - -<br>AccO14 Pc HP HP 8300 SFF SGH331ROP9 - - @ Available - - -<br>ACCO13 PC Acer Aspire MC60S — DISM1ST031332058993000 - - @ Available - - -<br>ACCO12 Keyboard Logitech K120 2204MR233A18 Satang (Full-Time) T @ InUse 2025-07-09 - -<br>ACCO11. ~Mouse = INPHIC PM6BS - ~ - @ Retired - - -<br>ACCO10 Mouse Logitech B100 2445APVOXKSB: = - @ Available - - -<br>ACCOO9 Mouse Logitech M100r 1748HS037TAS - - @ Available - - -<br>Previous 3 | 2 Next<br><!-- End of picture text -->



<!-- Start of picture text -->
7 4 =<br>Stock Supply reverting<br>ff Dashboard<br>© Monitor<br>Maintenance<br>& Laptop<br>Accessories<br>i3<br>D Histor J All Devices<br>& Employees<br>+ Add Device Accessories ya Laptop K Monitor K<br>© Logout 1 Unit: 5 UnitsJ 1 Unit<br>— 4% 71% — 4%<br>Device |Search Device Search<br>1D Category Brand Model Serial No. Owner Department Status Repair Date Details Action<br>ACCO19 Accessories HXS) 6D Optical - - @ Maintenance 2025-07-04 Battery Is Dead, Needs To<br>Mouse Stay Plugged In.<br>LAPOO3 = Laptop = Lenovo Thinkpad 4805 PC-OW9SH2 - @ Maintenance 2025-07-10 No Battery<br>LAPOOG Laptop Acer SWIFT SF314- NXGNUSTO2772502FB57200 - @ Maintenance 2025-07-02 Battery Is Worn Out<br>52<br>LAPOO7 Laptop ‘HP 13-AGOO00AU_ 8CG9130FOX - (@ Maintenance 2025-07-02 Alt Key Missing<br>LAPO17 Laptop Asus UX410U, H2NOCV14942808C - @ Maintenance 2025-07-10 Rattery Is Worn Out<br>‘LAPO31 Laptop Dell Asdasdsa Dasdad Bank S. (Intern) T @ Maintenance 2025-07-17 Hbhhh<br>MONO10 = Monitor Dell U2312HMt = CN-OO2WKF.-744452BEB5ZL Bank S. (Intern) T @ Maintenance 2025-07-03 Dfdfsst<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosne o<br>4 Dashboard<br>= oes History<br>:+ add——Device MaintcnancsMaintenanceAction 20257098-07-9807Date23 08.06.94014716 DaviesDevice IP1D MONOIOLAPOSTay SatSct ToToMaintenance. Maintenance. Narawith@Ths-Marketing.comNarswith@otbs—Markctingcom emsMonitorLaptop Bankexrankanterm) Action<br>Ge Legeut Receive 2078-07-23 01.4687 Ravice ID 1 APO31 Received And Assigned To Owner Narawith@Ths-Marketing.Com, laptop: Rank (latarn)<br>Add Device 2025 07 23 014634 Device 1D LAPOST Was Added To The System Narswith@otbs Marktingcom ——baptop<br>Receive 025.07 27982747 Peview IM ACENSS Receved And Assigned! Ta Owner Narawlt@Ths-Marketing.om —Acressories Sumo (Rull-Timed<br>Receive POPS.07 2798781 Review IM AEN Received And Assigned Ta Owner ——-Narawith@Ths MarketingCom Accessories ile Full-Time)<br>ReceiveReceive «2025POPS.07 2707 22082435 ORPRAS Device——_Paview IM1D ACKDITACCOU ReceivedRecewed AndAnd ActignedAecigned TaTo OwnerOwner _—sNarawithep——_-Narawithi@TheIbe MarketinacomMarketing Com —Accezzories—Accestories Mummywile (Full (Full-Time)Time)<br>Receive «2025 07 22 08:23:01 Device ID ACCOTG Recewwed And Acsigned To Owner _—=sNarawithep Ibe Marketinacom —Aecezzories Park (Full Time)<br>Receive «209 07 BERTIE Device 1D ACCOTZ Received And Assigned fo Owner _—«sNarawithenibe Markctinacom Accessories satang (ull Time)<br>Receive P0P5-07-77 0:18:29 Preview If) ACEOOR Received And Actigned Te Owner ——-Narawith@Ths-Marketing.Com Accasiories Rank (latern)<br>Receive «2029 07 ZZOETEDB —_Device 1D ACCUGE Recewved And Assigned fo Owner _—«Narawithenibs Markcting.om Accessories Wank (intern)<br>Receive 2025-07-72 08:12:29 Preview If) ACEOOI Received And Actigned To Owner _—Narawith@The-Marketing.Com  Accestories —Pearchan antern)<br>Add Device Zou 07 22 08Ws0 evice 1D ACCUIS Was Added to the Syctem Narswithentbs Markctingcom —Aecessories<br>Aad Device 7025-07-97 08:08:31 Device ID ACCOR? Was Adced To The System Narawith@'The-Markating.Com Accessories :<br>Add Device gous of we oROra1 bevice 1 ACCU Was Added to the System Narswithea ibs Marketingcom Accessories<br>Ada Pevieo 2028-07-27 0807.11 evice I ACCORD Wns Adiod To The Systom Narawith@Ths MarketingCom Accessories -<br>Add Rovio 2028-07 27 a7 8001 Device ID ACCOPA Ws Adtod To The Systom Narawith@Ths Marketingcom Accessories :<br>hotrod 2028.07 22 7-48.47 Povice ID ACCOPE Set To Rotirad Narawith@Ths Marketingcom Accessories<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmarkeving ©<br>Dashboard<br>© Monitor<br>Employees<br>& Laptop<br>s& Accessories<br># Maintenance Full-Time Tv Intern 2<br>D History<br>32 Persons 25 Persons<br>Employees ooo<br>+ Add Device<br>G Logout AlllEmployees<br>57<br>S<br>NickName FirstName LastName Department Position Action<br>Megan SEO Intern<br>Non Graphic Full-Time<br>Pim Graphic Full-Time<br>Kowkong Graphic Full-Time<br>Surf SEO Intern<br>Poom PBN Full-Time<br>Nam Tv Full-Time<br>Magan SEO Intern<br>Ploy SEM Intern<br>Nerd Content Writer(TH) Full-Time<br>Ruj SEO Full-Time<br>First Content Full-Time<br>Noey sto Full-Time<br>Bille Sale Full-Time<br>Satang Full-Time<br>Bank Janprasert Sutanai T Intern<br>Nui Content Writer(TH) Full-Time<br>Khao SEO & SEM Intern<br>Nongnaphat Content Intern<br>Eve SEO Intern<br>Golf SEM Full-Time<br>Ton-Orr Content Intern<br>Bam Content Full-Time<br>Tuk Account Full-Time<br>Alex SEO Full-Time<br>Previous | 2 3 Next<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmarnoniog ©<br>Dashboard<br>© Monitor<br>& Laptop Add Device<br>& Accessories<br># Maintenance Add Device<br>D History Category DevicelD<br>& Employees -- Select -- y<br>Add Device Bene-- Select Brand -- ¥} Nese<br>© Logout<br>Serial No Keyword<br>= Select -- .<br>status Add Device Date<br>Available Mm/Dd/Yyyy o<br>@Q=<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmarnening ©<br>Dashboard<br>© Monitor<br>Accessories<br>& Laptop<br>> Accessories Edit Device<br># Maintenance Category. DevicelD<br>D History Accessories » ACCO33<br>4 Brand ‘Status<br>& Employees Logitech ¥ @ In Use »<br>© Add Device<br>Keyword, Model<br>Logout Keyboard ¥) | K270<br>Serial Number ‘Add Device Date<br>2149S YO3QKFS 07/01/2025 o<br>sa<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmartening ©<br>Dashboard<br>© Monitor<br>Accessories<br>& Laptop<br>— Device. Details:’  ACCO31 (KM400) ©" Seach<br># Maintenance El Device<br>S History ID: ACCO31 Brand : Marvo SerialNumber: 20190100183<br>©: Employees Category:Accessories Model : KM400_ Status :<br>* Add Device<br>© Logout © History.<br>Action Date Description User Category Owner<br>Receive 2025-07-22 08:26:59 Device ID ACCO31 Received And Assigned To Owner Narawith@Tbs-Marketing.Com Accessories March (Full-Time)<br>Add Device 2025-07-22 08:07:41 Device ID ACCO31 Was Added To The System Narawith@Tbs-Marketing.Com Accessories -<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmartening ©<br>Dashboard<br>© Monitor<br>Laptop<br>& Laptop<br>de Accessories Receive* Device:<br># Maintenance DevicelD<br>vsp022<br>D History<br>Owner Department<br>© Employees Nickname -- Select --<br>* Position: Receive Date<br>Add Device Select vy) ( MmyDayyyyy o<br>C Logout<br>ns<br><!-- End of picture text -->



<!-- Start of picture text -->
Stock Supply tosmardering ©<br>Dashboard<br>© Monitor<br>Dashboard<br>& Laptop<br>.de Accessories Form Maintenance.<br># Maintenance Device Information<br>D History Device ID Brand<br>ACCOO2 Logitech<br>& Employees<br>* Category Model<br>Add Device Accessories: K120<br>Logout<br>Serial Number<br>(2204MR233A38°<br>Maintenance<br>Repair Date.<br>Mm/Dd/Yyyy o<br>Detaits<br>ecx=><br><!-- End of picture text -->

50 

###### **4.3 ผลการประเมินโครงงานโดยกลุ่มผู้ดูแลระบบ** 

จากการทดสอบโดยกลุ่มผู้ดูแลระบบและผู้จัดการ จำนวน 3 คน พบว่า 

|เรองทประเมน|มากทสด|มาก|ระด<br>ปานกลาง|บความ<br>นอย|พงพอใจ<br>นอยทสด|<br>คาเฉลย|ะดบความพงพอใจ|
|---|---|---|---|---|---|---|---|
||5|4|3|2|1||ร|
|1. ความงายตอการใชงานของระบบ|2|1|-|-|-|4.67|มากทสด|
|2. ความเรวในการโหลดและประมวลผลขอมล|1|2|-|-|-|4.33|มาก|
|3. ความสะดวกในการคนหาและเบกจายอปกรณ|1|2|-|-|-|4.33|มาก|
|4. ความชดเจนและความถกตองของขอมล<br>อปกรณ|1|1|1|-|-|4.0|มาก|
|5. การออกแบบและความเปนระเบยบของ<br>อนเทอรเฟซ|1|1|1|-|-|4.0|มาก|



ตารางที่ 4.1 ตารางสรุปการประเมินโครงงานโดยกลุ่มผู้ดูแลระบบ 

###### **4.3.1 ความง่ายต่อการใช้งานของระบบ** 

ผู้ใช้งานมีความพึงพอใจในระดับมากที่สุด จำนวน 2 คน คิดเป็นร้อยละ 66.67 ผู้ใช้งานมี ความพึงพอใจในระดับมาก จำนวน 1 คน คิดเป็นร้อยละ 33.33 โดยรวมผู้ใช้งานเห็นว่าระบบสามารถ ใช้งานได้ง่าย เข้าถึงเมนูต่าง ๆ ได้สะดวก และไม่ต้องใช้เวลาศึกษานาน 

###### **4.3.2 ความเร็วในการโหลดและประมวลผลข้อมูล** 

ผู้ใช้งานมีความพึงพอใจในระดับมากที่สุด จำนวน 1 คน คิดเป็นร้อยละ 33.33 และในระดับ มาก จำนวน 2 คน คิดเป็นร้อยละ 66.67 แบบฟอร์มบันทึกการซ่อมบำรุงสามารถกรอกข้อมูลได้ง่าย และครบถ้วน 

51 

###### **4.3.3 ความสะดวกในการค้นหาและเบิกจ่ายอุปกรณ์** 

ผู้ใช้งานมีความพึงพอใจในระดับมากที่สุด จำนวน 1 คน คิดเป็นร้อยละ 33.33 ผู้ใช้งานมี ความพึงพอใจในระดับมาก จำนวน 2 คน คิดเป็นร้อยละ 66.67 ระบบสามารถค้นหาอุปกรณ์ได้อย่าง รวดเร็วและแม่นยำ รวมถึงขั้นตอนการเบิกจ่ายที่ไม่ซับซ้อน ทำให้การทำงานมีประสิทธิภาพมากขึ้น 

###### **4.3.4 ความชัดเจนและความถูกต้องของข้อมูลอุปกรณ์** 

ผู้ใช้งานมีความพึงพอใจในระดับมากที่สุด จำนวน 1 คน คิดเป็นร้อยละ 33.33 ผู้ใช้งานมี ความพึงพอใจในระดับมาก จำนวน 1 คน คิดเป็นร้อยละ 33.33 และในระดับปานกลาง จำนวน 1 คน คิดเป็นร้อยละ 33.33 ระบบแสดงข้อมูลอุปกรณ์ เช่น ยี่ห้อ รุ่น รหัส และสถานะ ได้ครบถ้วน ถูกต้อง และสามารถค้นหาได้ง่าย 

###### **4.3.5 การออกแบบและความเป็นระเบียบของอินเทอร์เฟซ** 

ผู้ดูแลระบบและผู้จัดการมีความพึงพอใจในระดับ มากที่สุด จำนวน 1 คน คิดเป็นร้อยละ 33.33 และในระดับ มาก จำนวน 1 คน คิดเป็นร้อยละ 33.33 รวมทั้งในระดับ ปานกลาง จำนวน 1 คน คิดเป็นร้อยละ 33.33 การออกแบบอินเทอร์เฟซมีความเรียบง่าย สบายตา จัดวางเมนูเป็นระเบียบ และง่ายต่อการค้นหาข้อมูล 

52 

###### **บทที่ 5** 

###### **สรุปผลการวิจัยและข้อเสนอแนะ** 

ผลการดำเนินงานโครงงานระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT สามารถ ดำเนินงานได้ตามขอบเขตที่ตั้งไว้ ซึ่งสามารถสรุปผลการดำเนินงาน ปัญหา อุปสรรค และข้อจำกัดใน การจัดทำโครงงานได้ดังนี้ 

###### **5.1 สรุปผลการดำเนินงาน** 

ในช่วงระยะเวลา 6 เดือนของการดำเนินงาน ทีมงานได้พัฒนาระบบบริหารจัดการสินทรัพย์ และคลังอุปกรณ์ IT ซึ่งเป็นระบบสนับสนุนการจัดการสต็อกและการควบคุมการเบิกจ่ายอุปกรณ์ ภายในองค์กร โดยได้ทำการศึกษาข้อมูลเกี่ยวกับกระบวนการจัดการสต็อกในองค์กรจริง รวมถึงการ ใช้เทคโนโลยี WordPress และปลั๊กอินเสริมต่าง ๆ เพื่อให้ระบบสามารถทำงานได้อย่างครบวงจร นอกจากนี้ ทีมงานยังได้มีการตรวจสอบความคืบหน้าและขอคำแนะนำจากอาจารย์ผู้สอนอย่าง ต่อเนื่องเพื่อให้การพัฒนาระบบเป็นไปอย่างถูกต้องและตรงตามความต้องการ 

อย่างไรก็ตาม ในกระบวนการพัฒนานี้ ทีมงานเผชิญกับอุปสรรคหลายประการ โดยเฉพาะใน ด้านการวางแผนเลือกปลั๊กอินและการปรับแต่งฟังก์ชัน เนื่องจากปลั๊กอินบางส่วนที่ต้องการใช้งานมี ข้อจำกัดหรือมีค่าใช้จ่ายเพิ่มเติม ซึ่งแตกต่างจากข้อมูลที่ได้ศึกษามาก่อนหน้านี้ ส่งผลให้ต้อง ปรับเปลี่ยนแผนการดำเนินงานและออกแบบฟังก์ชันบางส่วนขึ้นมาใหม่ด้วยการพัฒนาโค้ดเพิ่มเติม 

จากผลการประเมินและการทดสอบโดยผู้ใช้งาน พบว่าผู้ใช้มีระดับความพึงพอใจสูงในหลาย ด้าน โดยเฉพาะในด้าน ความง่ายในการใช้งาน ซึ่งผู้ใช้สามารถค้นหาอุปกรณ์ ตรวจสอบสถานะสต็อก และทำรายการเบิกจ่ายได้อย่างสะดวก นอกจากนี้ ความเร็วในการโหลดระบบ ได้รับการตอบรับที่ดี เนื่องจากสามารถตอบสนองการทำงานได้รวดเร็วแม้มีผู้ใช้งานหลายคนพร้อมกัน 

ในด้าน ความชัดเจนของการแสดงข้อมูลสต็อก ผู้ใช้งานให้การตอบรับเชิงบวก โดยเฉพาะ รายละเอียดที่ครบถ้วน เช่น จำนวนข้อมูลที่ใช้ว่าง หรือกำลังใช้งานอยู่ วันที่มีการปรับปรุงข้อมูลล่าสุด และประวัติการยืมคืน ส่วนการออกแบบและความสวยงามของระบบก็ได้รับความพึงพอใจเช่นกัน เนื่องจากอินเทอร์เฟซมีความเป็นระเบียบและเข้าใจง่าย 

ผลการประเมินเหล่านี้ช่วยให้ทีมพัฒนาระบบเข้าใจพฤติกรรมและความต้องการของผู้ใช้งาน ได้ดียิ่งขึ้น และสามารถนำข้อมูลไปใช้ปรับปรุงและพัฒนาระบบบริหารจัดการสินทรัพย์และคลัง อุปกรณ์ IT ให้มีประสิทธิภาพสูงขึ้นในอนาคต เช่น การเพิ่มระบบแจ้งเตือนสต็อกต่ำ การสร้างรายงาน อัตโนมัติ และการเชื่อมต่อกับระบบจัดการอื่น ๆ ภายในองค์กร 

53 

###### **5.2 สรุปผลการประเมินโดยกลุ่มผู้ดูแลระบบ** 

การดำเนินโครงการระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ได้ทำการประเมินความพึง พอใจของกลุ่มผู้ดูแลระบบ (Admin) ซึ่งเป็นผู้รับผิดชอบในการจัดการข้อมูลสต็อกและควบคุมการ เบิกจ่ายอุปกรณ์ภายในองค์กร โดยใช้แบบสอบถามความพึงพอใจ พร้อมกำหนดเกณฑ์การแปลผล ข้อมูลจากค่าเฉลี่ยดังนี้ 

- ค่าเฉลี่ยตั้งแต่ 4.50 ขึ้นไป : มีความพึงพอใจมากที่สุด 

- ค่าเฉลี่ยตั้งแต่ 3.50 – 4.49 : มีความพึงพอใจระดับมาก 

- ค่าเฉลี่ยตั้งแต่ 2.50 – 3.49 : มีความพึงพอใจระดับปานกลาง 

- ค่าเฉลี่ยตั้งแต่ 1.50 – 2.49 : มีความพึงพอใจระดับน้อย 

- ค่าเฉลี่ยต่ำกว่า 1.50 : มีความพึงพอใจน้อยที่สุด 

โดยสามารถสรุปผลความพึงพอใจระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ได้ดังนี้ 

|เรองทประเมน|มากทสด|มาก|ระด<br>ปานกลาง|บความ<br>นอย|พงพอใจ<br>นอยทสด|<br>คาเฉลย|ะดบความพงพอใจ|
|---|---|---|---|---|---|---|---|
||5|4|3|2|1||ร|
|1. ความงายตอการใชงานของระบบ|2|1|-|-|-|4.67|มากทสด|
|2. ความเรวในการโหลดและประมวลผลขอมล|1|2|-|-|-|4.33|มาก|
|3. ความสะดวกในการคนหาและเบกจายอปกรณ|1|2|-|-|-|4.33|มาก|
|4. ความชดเจนและความถกตองของขอมล<br>อปกรณ|1|1|1|-|-|4.0|มาก|
|5. การออกแบบและความเปนระเบยบของ<br>อนเทอรเฟซ|1|1|1|-|-|4.0|มาก|



ตารางที่ 5.1 ตารางสรุปผลทดสอบโดยกลุ่มผู้ดูแลระบบและผู้จัดการ 

54 

###### **5.2.1 ความง่ายต่อการใช้งานของระบบ** 

ผู้ดูแลระบบและผู้จัดการมีความพึงพอใจในระดับมากที่สุด โดยทั้งหมด 3 คนรู้สึกว่าระบบ บริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ใช้งานง่าย สามารถเข้าถึงเมนูต่าง ๆ ได้สะดวก และเข้าใจ ขั้นตอนการใช้งานโดยไม่ต้องใช้เวลาศึกษานาน ซึ่งสะท้อนถึงความเป็นมิตรต่อผู้ใช้ (User-Friendly) ของระบบ 

###### **5.2.2 ความเร็วในการโหลดและประมวลผลข้อมูล** 

ผู้ดูแลระบบและผู้จัดการมีความพึงพอใจในระดับมาก โดยเห็นว่าระบบสามารถโหลดข้อมูล และประมวลผลคำสั่งได้รวดเร็ว ตอบสนองต่อการใช้งานได้อย่างทันท่วงที แม้ในช่วงที่มีการใช้งาน พร้อมกันหลายคน 

###### **5.2.3 ความสะดวกในการค้นหาและเบิกจ่ายอุปกรณ์** 

ผู้ดูแลระบบและผู้จัดการมีความพึงพอใจในระดับมาก แสดงให้เห็นว่าระบบสามารถค้นหา อุปกรณ์ได้รวดเร็วและแม่นยำ รวมถึงขั้นตอนการเบิกจ่ายที่ไม่ซับซ้อน ทำให้การทำงานประจำวันของ เจ้าหน้าที่มีประสิทธิภาพมากขึ้น 

###### **5.2.4 ความชัดเจนและความถูกต้องของข้อมูลอุปกรณ์** 

ผู้ดูแลระบบและผู้จัดการมีความพึงพอใจในระดับมาก แสดงให้เห็นว่าระบบมีการแสดงข้อมูล สต็อกที่ถูกต้อง ครบถ้วน และอัปเดตอย่างต่อเนื่อง เช่น จำนวนคงเหลือ สถานะการใช้งาน และ ประวัติการยืมคืนอุปกรณ์ 

###### **5.2.5 การออกแบบและความเป็นระเบียบของอินเทอร์เฟซ** 

ผู้ดูแลระบบและผู้จัดการมีความพึงพอใจในระดับมาก โดยชื่นชมว่าการออกแบบอินเทอร์เฟซ ของระบบ Stock Supply มีความเป็นระเบียบ สบายตา และง่ายต่อการค้นหาข้อมูลหรือเมนูที่ ต้องการ ทำให้ประสบการณ์การใช้งานโดยรวมอยู่ในระดับที่ดีมาก 

###### **5.3 ข้อจำกัดของโครงการ** 

ในระหว่างการดำเนินโครงการพัฒนาระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT ทีมงานพบ ข้อจำกัดและอุปสรรคหลายประการที่ส่งผลต่อกระบวนการพัฒนาและการออกแบบระบบ ดังนี้ 

###### **5.3.1 ข้อจำกัดด้านปลั๊กอินและเทคโนโลยี** 

ปลั๊กอินที่ต้องการใช้งานบางส่วนมีข้อจำกัดในด้านฟังก์ชันการใช้งาน หรือมีค่าใช้จ่ายเพิ่มเติม ซึ่งแตกต่างจากข้อมูลที่ได้ศึกษาก่อนเริ่มโครงการ ส่งผลให้ทีมงานต้องปรับเปลี่ยนแผนการเลือกใช้ ปลั๊กอินและพัฒนาฟังก์ชันบางส่วนขึ้นมาเองด้วยการเขียนโค้ดเพิ่มเติม 

55 

###### **5.3.2 ข้อจำกัดด้านการวางแผนและการปรับแต่ง** 

การปรับแต่งระบบให้ตอบสนองความต้องการเฉพาะขององค์กรเป็นเรื่องที่ต้องใช้เวลาและ ความชำนาญสูง ทำให้บางครั้งเกิดความล่าช้าในการพัฒนาระบบ และต้องมีการปรับเปลี่ยนแนว ทางการออกแบบในระหว่างดำเนินงาน 

###### **5.3.3 ข้อจำกัดด้านทรัพยากรและเวลา** 

การพัฒนาระบบภายในระยะเวลา 6 เดือนทำให้ทีมงานต้องบริหารจัดการเวลาอย่างเข้มงวด ส่งผลให้บางฟีเจอร์อาจยังไม่สมบูรณ์หรือไม่ครอบคลุมความต้องการทั้งหมดของผู้ใช้งานในช่วงแรก 

###### **5.3.3 ข้อจำกัดด้านความหลากหลายของผู้ใช้งาน** 

ระบบต้องรองรับผู้ใช้งานหลายระดับและความต้องการที่แตกต่างกัน ทำให้การออกแบบ อินเทอร์เฟซและฟังก์ชันต้องเป็นไปอย่างสมดุลเพื่อความสะดวกและเหมาะสมสำหรับผู้ใช้กลุ่มต่าง ๆ 

###### **5.4 ข้อเสนอแนะในการพัฒนา** 

เพื่อให้ระบบบริหารจัดการสินทรัพย์และคลังอุปกรณ์ IT สามารถตอบสนองความต้องการ ของผู้ใช้งานได้มากขึ้นและเพิ่มประสิทธิภาพในการทำงาน มีข้อเสนอแนะดังนี้ 

###### **5.4.1 การอัพเกรดปลั๊กอินเป็นเวอร์ชัน Pro หรือพิจารณาใช้ปลั๊กอินเสริมอื่น** 

การอัพเกรดปลั๊กอินที่ใช้งานเป็นเวอร์ชัน Pro หรือเลือกใช้ปลั๊กอินเสริมอื่น ๆ จะช่วยเพิ่ม ฟังก์ชันสำคัญ เช่น ระบบแจ้งเตือนสต็อกต่ำ การรายงานอัตโนมัติ และระบบควบคุมสิทธิ์การเบิกจ่าย ที่ครบถ้วนมากขึ้น 

###### **5.4.2 การปรับปรุงประสบการณ์ผู้ใช้ (UX)** 

ควรทำการวิจัยและทดสอบประสบการณ์ผู้ใช้เพื่อปรับปรุงการนำทางและความสะดวกในการ ใช้งาน เช่น การออกแบบเมนูที่ใช้งานง่ายและจัดระเบียบข้อมูลสต็อกให้อ่านง่าย เพื่อเพิ่มความพึง พอใจของผู้ใช้ 

###### **5.4.3 พิจารณาฟังก์ชันยืนยันตัวตนเพิ่มเติม** 

การพัฒนาฟังก์ชันยืนยันตัวตนผ่านระบบ OTP หรือการยืนยันผ่าน Email จะช่วยเสริมความ ปลอดภัยและความน่าเชื่อถือของระบบ คุ้มครองข้อมูลและสิทธิ์การเข้าถึงของผู้ใช้งานอย่างมี ประสิทธิภาพ 

56 

###### **บรรณานุกรม (Bibliography)** 

WPBeginner. (2021). What Is PHP? How Is PHP Used in WordPress? 

https://www.wpbeginner.com/glossary/php/ 

Truehost. (2024, August 1). How to Build a WordPress Website with Astra and 

Elementor 

https://www.truehost.com/how-to-build-a-wordpress-website-with-astra-and- 

elementor/ 

Newcomer, C. (2025, January 31). Beaver Builder review: Honest thoughts + pros and cons. WPKube. 

https://www.wpkube.com/wp-beaver-builder-wordpress-plugin/ 

SweetAlert2. (2025). SweetAlert2: A beautiful, responsive, customizable 

https://sweetalert2.github.io/ 

Bootstrap. (2025). Bootstrap v5.3: The most popular HTML, CSS, and JS library in the 

world. 

https://getbootstrap.com/ 

WordPress Contributors. (2025). wpdb – Class. WordPress Developer Resources. 

https://developer.wordpress.org/reference/classes/wpdb/ 

57 

**ภาคผนวก** 



<!-- Start of picture text -->
Stock Supply W<br>tos<br><!-- End of picture text -->

Stock Supply W tos Stocktee SupplPply WG 

WG 

| trons 



<!-- Start of picture text -->
@ Dashboard . .<br>Home l Leave A Review?<br>: > ;<br>2 a a<br>P Post Dashboard<br>Q) Media °<br>ime Thank you for installing Menu Icons!<br>Have you heard about our latest FREE theme - Neve? Usinga mabile-fst approach, compatibilay with AMP and popular page-builders, Neve makes website building accessible for everyone.<br>F Comment:<br>PF Appearance E<br>ese 5) Starter Templates<br>f Build Your Dream Site in Minutes With Al nl<br>“ r ate prot es in mi RESTAUR zs re: Hy > |<br>== ‘ i,wr y Rag Lecameenteneeeal Py 7"<br>@ PHP Update Recommended . Quick Draft .<br>asia<br>atria ae y Tl] WorarressEventi sndl Noms -<br>yaaahax. cleat te i 4 2" Toneee ° ecember 13-14 202<br>s<br>©<br>a B news<br>& WoraPress Guidea Tutorials ,<br><!-- End of picture text -->



<!-- Start of picture text -->
‘ a<br>Stock Supply tessreeuing<br>@ Dashboard<br>1 Monitor<br>Dashboard<br>& Laptop<br>t& Accessories<br># Maintenance Pivailable In Use ) i ne K‘ Retired 100ay<br>D History ;<br>Fir mn 68 Units 82 Units } 8 Units<br>41% 49% 5% - 5%<br>* Add Device<br>& Logout All Devices All Monitor All Laptop All Accessories<br>166 3 Re, 49Unit 1 32Unit 5») 85Unit<br>Device Search Device. Select Status ~ Search<br>ID Category Brand Model Serial No. Owner Department Status —Receive Date Return Date Repair Date Action<br>MON049 Monitor Dell $2440L CNOOJVDR39KOW0Q Tuk (Full-Time) Account @inuse 2025-07-04 - - i]<br>MON048 Monitor AOC £2470SWH F98G8BA001014 Tuk (Full-Time) Account @invse — 2025-07-04 - - i<br>MONO47 Monitor = LG E2240T-PN 003UXMT30531 - - @ Available - 2025-08-07 - }<br>MONO046 Monitor AOC 24G2SE YTJPSHAO00496 - - @ Available - 2025-08-07 - ay<br>MONO045 Monitor Samsung  S24F3S0FHE ZZNPH4Z)301199A Winner (Full-Time) SEO @inUse 2025-07-04 - -<br>MONO044 Monitor Dell £1911 CN-0CGH4M-64180-23F-02CS - - @ Available - 2025-08-07 : ‘ll<br>MON043 Monitor Acer G235H ETLK60C0231030402F4012 emai (Full-Time) SEM @invse 2025-07-02 : :<br>MONO42 Monitor Asus ve278 DILMTFO36611 Non (Full-Time) Graphic @inuse 2025-07-04 - -<br>MONO041 Monitor BenQ GL2760-8 ET8BFO2664SLO Non (Full-Time) Graphic @ in Use 2025-07-03 - - 7<br>MONO40MONO39 MonitorMonitor BenQLG 24MK430HGL2760-B ETB8FO274SSLO1OAINTXOW137 PimPim (Full-Time)(Full-Time) GraphicGraphic @invse@inse 2025-07-022025-07-03 -: -- ;}<br>MONO038 Monitor Acer VG240¥ MMTFSS00193300C102400 Amber (Full-Time) SEM @inse 2025-07-02 - - jj<br>MONO037 Monitor Acer K242Ht MMT1MSS004619069354200 Golf (Full-Time) SEM @invse 2025-07-04 - - 1<br>MONO036 Monitor Asus P8278 ESLMTFO96610 Kowkong (Full-Time) Graphic @invse 2025-07-02 : - }<br>MON035 Monitor LG 27MP2SHQ-B A0SINUBSK214 Kowkong (Full-Time) Graphic @invse 2025-07-02 - - ]<br>MONO034 Monitor Samsung  S24R3SAFHE SDNCH4TTB00351D - - @ Available - : - x<br>MONO033 Monitor Dell P2214Hb CN-OCY84D-74261-46D-1628 - - @ Available - 2025-08-07 - 7<br>MON032 Monitor Dell U2212HMc —-_-CN-OGCCD2-64180-252-09LL_ First (Full-Time) Content. @inUse 2025-07-12 - - i<br>MONO31 Monitor Acer S230HL MMLTSSS00332208A812401 —_—Ruj (Full-Time) SEO @inuse 2025-07-13 - - e<br>MONO030 Monitor Dell U2212HMc ~——CN-OY7MS5-64180-37A-08ZL_ Bam (Full-Time) Content @invse 2025-07-04 : : a<br>MONO029 Monitor Acer K242Ht MMTOFSS001615012068500 - - @ Available - - - 3<br>MONO028 Monitor AOC 24G2E ATNL41A020672 Toddy (Full-Time) SEO @invse 2025-07-10 : -<br>MON027 Monitor Dell U2212HMc —_CN-OY7M55-64180-37A-ONHL —Noey (Full-Time) SEO @inuse 2025-07-09 - - ;<br>MONO26MONO2S MonitorMonitor LenovoDell ThinkVision£203H 24-10 (CN-O1X2HC-64180-349-1PZM 61B7JAR6EWWV90SMSS9 -- -- @@ AvailableAvailable -- :- -- :}<br>Previous + | 2 | 3 Bea) 7 | net<br><!-- End of picture text -->



<!-- Start of picture text -->
Available J] In Use ) e1 %% Retired \<br>68 Units 82 Units its. 8 Units<br>41% 49% 5% . 5%<br>All Devices All Monitor All Laptop All Accessories<br>166 » 49Unit 19%“\ 32Unit 5») 85Unit<br>-- Select Status --<br>-- Select Status --<br>Available<br>In Use<br>Maintenance<br>Retired<br><!-- End of picture text -->



<!-- Start of picture text -->
-- Select Status --<br>-- Select Status --<br>Available<br>In Use<br>Maintenance<br>Retired<br><!-- End of picture text -->

Device Search Device... 



<!-- Start of picture text -->
__ Select Status -- | Search<br><!-- End of picture text -->



<!-- Start of picture text -->
i Edit<br>Q, View Details<br>® Receive<br>%€ Maintenance<br>@ Retired<br>@ Delete<br><!-- End of picture text -->

TE Edit 

2. View Details 

Return 

% Maintenance 

- @ Retired 

@ Delete 

LF Edit 

Q. View Details 

@ Available 

- @ Retired 

® Delete 

tt Edit 

Q, View Details 

@ Available 

W Delete 



<!-- Start of picture text -->
Edit Device<br>Category DevicelD<br>Monitor v MON048<br>Brand Status<br>AOC v @ In Use v<br>Keyword Model<br>Monitor v E2470SWH<br>Serial Number Add Device Date<br>F98G8BA001014 07/03/2025 06<br><!-- End of picture text -->

Devicei updated!I 



<!-- Start of picture text -->
Monitor.<br>Device. Details:H MON049 (S2440L) °°"Searcl retails. Seach<br>[) Device<br>ID : MONO49 Brand : Dell SerialNumber :CNOO/VDR39KOW0Q<br>Category : Monitor Model :S2440L Status : @ In Use<br>5 History<br>Action Date Description User Category Owner<br>Receive 2025-07-30 09:31:37 Device ID MONO049 Received And Assigned To Owner Narawith@Tbs-Marketing.Com Monitor Tuk<br>Add Device 2025-07-30 09:30:37 Device ID MON049 Was Added To The System Narawith@Tbs-Marketing.Com Monitor -<br><!-- End of picture text -->



<!-- Start of picture text -->
Add Device<br>Category DevicelD<br>Laptop v LAPO33<br>Brand Model<br>Asus v RER<br>Serial No Keyword<br>SE1254548 Laptop Y<br>Status Add Device Date<br>Available 08/05/2025<br><!-- End of picture text -->

###### Added laptop successfully 



<!-- Start of picture text -->
Receive Device<br>Position Receive Date<br>Intern ¥) | 07/31/2025<br><!-- End of picture text -->

Receive Device! 

###### Return Success!. 



<!-- Start of picture text -->
Form Maintenance°<br>Device Information<br>Device ID Brand<br>LAP033 Asus<br>Category Model<br>Laptop RER<br>Serial Number<br>SE1254548<br>Maintenance<br>Repair Date<br>Mm/Dd/Yyyy fa)<br>Details<br><!-- End of picture text -->

###### Retired Success! 

###### Device Available! 

###### Are you sure? 



<!-- Start of picture text -->
‘ a<br>Stock Supply tessreeuing<br>Dashboard<br>© Monitor<br>Monitor<br>& Laptop<br>s& Accessories<br># Maintenance Available In Use ') nanc % i<br>D History<br>19 Units 29 Units<br>& Employees —_— 1% —_— 1% 1% %<br>* Add Device<br>& Logout All Monitor<br>>j 4<br>Unit<br>Device Search Device. search<br>ID Category Brand Model Serial No. Owner Department Status —Receive Date Return Date RepairDate Action<br>MON049 Monitor Dell s2440t CNOOJVDR39KOW0Q Tuk (Full-Time) Account @inUse 2025-07-04 - : y]<br>MON048 Monitor AOC £2470SWH F98G8BA001014 Tuk (Full-Time) Account @inuse 2025-07-04 - Bi<br>MONO47 Monitor — LG £2240T-PN 003UXMT30531 - - @ Available - 2025-08-07 - }<br>MONO46 Monitor AOC 24G2SE YTIPSHA000496 - - @ Available - 2025-08-07 : |<br>MONO45 Monitor Samsung — S24F3S0FHE ZZNPH4ZJ301199A Winner (Full-Time) SEO @inuse 2025-07-04 - - ‘<br>MON044 Monitor Dell £1911 CN-0CGH4M-64180-23F-02CS - - @ Available - 2025-08-07 - A}<br>MON043 Monitor Acer G235H ETLK60C0231030402F4012 emai (Full-Time) SEM @inse 2025-07-02 - - i<br>MONO042 Monitor Asus ve278 DILMTFO36611 Non (Full-Time) Graphic @invse — 2025-07-04 - - }<br>MON041 Monitor BenQ GL2760-8 ETBSFO2664SLO Non (Full-Time) Graphic @inUse 2025-07-03 : : A<br>MON040 Monitor BenQ G12760-B ETBBFO274SSLO Pim (Full-Time) Graphic @inse 2025-07-02 - -<br>MONO39 Monitor LG 24MK430H 1O4INTXOW137 Pim (Full-Time) Graphic @inuse 2025-07-03 - -<br>MON038 Monitor Acer vG240¥ MMTFSS00193300C102400 Amber (Full-Time) SEM @inse 2025-07-02 - : 8<br>MONO037 Monitor Acer K2a2HL MMT1MSS004619069354200 Golf (Full-Time) SEM @inuse — 2025-07-04 - - ¥<br>MONO036 Monitor Asus PB278 ESLMTFO96610 Kowkong (Full-Time) Graphic @inuse 2025-07-02 - - j .<br>MONO035 Monitor LG 27MP2SHQ-B 40SINUBSK214 Kowkong (Full-Time) Graphic @inuse 2025-07-02 : : }<br>MONO034 Monitor Samsung — S24R35AFHE SDNCH4TTBO0351D - - @ Available - - - 7<br>MON033 Monitor Dell P2214Hb CN-OCY84D-74261-46D-1G2B - - @ available - 2025-08-07 - y|<br>MONO032 Monitor Dell U2212HMc —-_-CN-OGCCD2-64180-252-09LL First (Full-Time) Content. @inUse — 2025-07-12 : -<br>MONO31 Monitor Acer S230HL MMLTSSS00332208A812401 uj (Full-Time) SEO @inse 2025-07-13 - - fA)<br>MON030MONO29 MonitorMonitor — AcerDell U2212HMcK242HL —-CN-OY7MSS-64180-37A-08ZLMMTOFSS001615012068500 Bam (Full-Time)- Content.- @@inuseAvailable 2025-07-04- -- -- AF<br>MONO028 Monitor AOC 24G2E ATNL41A020672 Toddy (Full-Time) SEO @inuse 2025-07-10 - - f<br>MONO027 Monitor Dell U2212HMc —_CN-OY7M55-64180-37A-ONHL —Noey (Full-Time) SEO @inse 2025-07-09 : : )<br>MONO26 Monitor Lenovo ThinkVision E24-10  61B7/AR6EWWV905MS59 - - @ Available - - : f<br>MONO25 Monitor Dell £203H CN-O1X2HC-64180-349-1PZM - - @ Available - - - e<br>Previous 3 | 2 Next<br><!-- End of picture text -->



<!-- Start of picture text -->
‘ a<br>Stock Supply tessreeuing<br>Dashboard<br>© Monitor<br>Laptop<br>© Laptop<br>s& Accessories<br># Maintenance aan F wT<br>D History i<br>& eeiayen 8 Units 20 Units 0 Unit<br>= - 5% - 12% 2% 0%<br>+ Add Device<br>G Logout All Laptop<br>wm 32Unit<br>Device Search Device Search<br>1D Category Brand Model Serial No. ‘Owner Department Status Receive Date Return Date Repair Date Action<br>LAP032 Laptop Lenovo 20UES13503 PF2ISZYG - - @ Available : - - ,)<br>LAPO31 Laptop Dell_~— ThinkPad T490 PFIY690Y - - @ Available - 2025-08-07 - : i<br>LAPO30 Laptop HP Pavilion X360 SCGO348GT7 = oe @ Available 2 2025-08-07 * a<br>LAPO29 Laptop Lenovo Thinkpad T480S PC-0W9S!9 Winner (Full-Time) SEO @inuse 2025-07-02 - - 2)<br>LAP028 Laptop Dell P102F 1049863 Stamp N, (Intern) Content @inuse 2025-08-07 - - =i<br>LAPO27 Laptop Lenovo 7480s PCOSSHQ Pemai (Full-Time) SEM @inuse 2025-07-08 - eal<br>LAPO26 Laptop Lenovo ThinkPad T14s Gen1 PCINCIES Amber (Full-Time) SEM @inUse 2025-07-07 - : _<br>LAPO25 Laptop Huawei KLVL-WFHS SYDBB20826801592 Nerd (Full-Time) Content Writer(TH) @ inuse 2025-07-04 = - ra<br>LAPO24 Laptop Lenovo ThinkBook 15 G2 ITL mp200/Q7 Ruj (Full-Time) SEO @inuse 2025-07-08 - - 2)<br>LAPO23 Laptop Lenovo 81x2 R9OZLFDH Bam (Full-Time) Content @inuse 2025-07-13 - -<br>LAPO22 Laptop Lenovo Thinkpad T 4805 PC-OW95GV - - @ Available - - - =)<br>LAP021 Laptop Lenovo Thinkpad T14 Gen 2 PF-303A4G - - @ Available - - - }<br>LAP020 Laptop Asus M5150 NINOCV180015039 First (Full-Time) Content @invse 2025-07-06 - - cal<br>LAPOIS Laptop HP ZBook 15 GS SCD9080xX9L Toddy (Full-Time) SEO @inuse 2025-07-14 - - ><br>LAPO18 Laptop Lenovo ThinkBook 15 G2 (TL MP22NED1 Noey (Full-Time) SEO @inuse 2025-07-09 )<br>LAPO17 Laptop Asus UX410U H2NOCV14942808C - - @ Maintenance - - 2025-07-10<br>LAPOI6 Laptop Acer Swift Sf314-S1__ NXGKLST022716000647200 : - @ Available : - - }<br>LAPOIS Laptop Lenovo Thinkpad X1 Yoga ROONHSVC Sumo (Full-Time) SEO @inuse 2025-07-03 - - ,<br>LAPO14 = Laptop HP Pavilion X360 2-In-1 8CG22801PT March (Full-Time) Sale @ in use 2025-07-16 S = =<br>LAPO13 Laptop Dell ~—_ Latitude 5310 2020AP1831 Bille (Full-Time) sale @inuse 2025-07-03 - -<br>LAPOT2 Laptop Huawei ——_KLVL-WFE9 6718820909800139 Bille (Full-Time) sale @inuse 2025-07-06 - - ,<br>LAPOT1 Laptop Dell —_ Latitude $421 SZL2FK3. Mummy N. (Full-Time) T @inuse 2025-07-10 - - ]<br>LAPO10 Laptop Asus 35000 M9N0CX26574739E Park F. (Full-Time) 1 @inuse 2025-07-02 i]<br>LAPOOS Laptop Lenovo Thinkpad 14 Gen2 PF-3Q3VLV Satang (Full-Time) T @ inuse 2025-07-16 as *<br>LAPOOB Laptop Acer ‘SWIFT SF314-51 —NXGKBSTO11728039177200 - = @ Available - - - 2<br>Previous + | 2 ~Next<br><!-- End of picture text -->



<!-- Start of picture text -->
=<br>Stock Supply tossrsering<br>ff Dashboard<br>© Monitor<br>Accessories<br>& Laptop<br>*%& Accessories<br># Maintenance aa Ty<br>D History<br>ai corierses 41 Units 33 Units 8 Unit<br>—_ 25% — 20% 2% e 5%<br>* Add Device<br>& Logout All Accessories<br>Unit<br>o») 85<br>Device Search Device. -- Select Keyword -- v Search<br>1D Category = Brand Model Serial No. Owner Department Status Receive Date Return Date Repair Date Action<br>ACCO8S Keyboard Logitech K120 230SMR13AA68 - - @ Available - 2025-08-07 -<br>ACCO64 = Mouse HXS) T24 . @ Available 2025-08-07<br>ACCO83 Keyboard Logitech K120 2051MR1795B8 - - @ Available - 2025-08-07 -<br>ACCO82 = Mouse INPHIC PM6 - - - @ Available - 2025-08-07 -<br>ACCO81 Mouse INPHIC PM6BS - ‘Winner (Full-Time) SEO @ in Use 2025-07-08 - -<br>ACCO80 Keyboard Logitech K120 2305MR13A568 - - @ Available - 2025-08-07 -<br>ACCO79. «Mouse INPHIC PM6 : : : @ Available - 2025-07-31 :<br>ACCO78 Keyboard Logitech K270 2152SYOB7HAS- Pemai (Full-Time) ‘SEM @ in Use 2025-07-05 - -<br>ACCO77-—- Mouse Logitech M185 21SOLZM27TV8 Pemai (Full-Time) SEM @ in use 2025-07-05 - -<br>ACCO76 Keyboard Logitech K270 2125SY010F8 Non (Full-Time) Graphic @ in Use 2025-07-04<br>ACCO7S5 Mouse INPHIC wi - Non (Full-Time) Graphic @ in Use 2025-07-03 - -<br>ACCO74 Keyboard Logitech K120 203S5MROCBE48 Pim (Full-Time) Graphic @ in Use 2025-07-04 - -<br>ACCO73 Mouse Zelotes T-30 3020230709189 Pim (Full-Time) Graphic @ in Use 2025-07-08 - -<br>ACCO72 Keyboard Logitech K120 2305MR13AA38 Amber (Full-Time) SEM @ in Use 2025-07-06 - -<br>ACCO71 Keyboard MD Tech K15+51 Usb 2G2017073000 Golf (Full-Time) SEM @ inuse 2025-07-04 : :<br>ACCO70 Keyboard Logitech K120 2204MR233708 Kowkong (Full-Time) Graphic @ in Use 2025-07-06 - :<br>ACCO6S) = Mouse INPHIC - - - - @ Available - - -<br>ACCO6B = Mouse INPHIC PM6 : : : @ Available - - -<br>ACCO067 = Mouse Zelotes T-30 T3020230709189 - - @ Available - - -<br>ACCO66 Keyboard Logitech K120 2051MR179SD8 - ~ @ Available - - -<br>ACCO6S Mouse Marvo: KM400 20190100153 Ruj (Full-Time) SEO @ in Use 2025-07-05 - -<br>ACCO64 Mouse MD Tech K15+51 Usb 2ZG2017073000 Bam (Full-Time) Content @ In Use 2025-07-05 - -<br>ACCO63 = Mouse Anitech w219 'W2192107 First (Full-Time) Content @ in use 2025-07-04 - -<br>ACC062 Keyboard Logitech K120 2051MR1795E8 Toddy (Full-Time) SEO @ in Use 2025-07-04 : -<br>ACCO61 Mouse INPHIC PM6BS - Toddy (Full-Time) SEO @ inuse 2025-07-04 - -<br>Previous + | 2 3 4 = Next<br><!-- End of picture text -->



<!-- Start of picture text -->
2<br>Stock Supply srtening<br>ff Dashboard<br>© Monitor<br>Maintenance<br>& Laptop<br>s& Accessories<br># Maintenance Total Devices<br>D History<br>& Employees<br>Add Device Accessories K Laptop K Monitor K<br>& Logoutst 3 Units 4 Units 1 Unit<br>38% 50% —_— 13%<br>1D Category Brand Model Serial No. Owner Department Status Repair Date Details Action<br>ACCOO4 Accessories Lenovo ADLX6SYLC3D 8SSA27FT Bam (Full-Time) Content @ Maintenance 2025-08-07 ChangeTo LAPO23<br>ACCOI9 Accessories HXSJ 6D Optical - - @ Maintenance 2025-07-04 _Batttery Is Dead, Needs To<br>Mouse Stay Plugged In.<br>ACCO4S Accessories LIMEIDE : : @ Maintenance 2025-07-03 1 Key Lost<br>LAPO03 Laptop Lenovo Thinkpad 4805 PC-OW9SH2 - @ Maintenance 2025-07-10 No Battery<br>LAPO06 = Laptop = Acer. SWIFT SF314-S2_ NXGNUST02772502FBS7200 - @ Maintenance 2025-07-02 BatteryIsWorn Out<br>LAPOO7 Laptop = HP 13-AGOOODAU. 8CG9130FOX : @ Maintenance 2025-07-02 Alt Key Missing<br>LAPOIT Laptop Asus Ux410U H2NOCV14942808C - @ Maintenance 2025-07-10 BatteryIs Worn Out<br>MONO10 Monitor Dell ~=—-U2312HMt_~=——CN-O02WKF-7444S2BEBSZL_—_ BankS. (Intern) is @ Maintenance 2025-07-03 Dfdfssf<br><!-- End of picture text -->



<!-- Start of picture text -->
. =<br>Stock Supply toscreating<br>@ = Dashboard<br>Monitor<br>History<br>& Laptop<br>+ Accessories<br>D History<br>Employees Delete DeviceAction 2025-08-14Date03:23:46 Deleted Device ID Description: LAPO33 - RER (SN: SE1254548) Narawith@Tbs-Marketing.comUser CategoryLaptop Owner= Action§ =<br>Add Device =<br>& Logout AvailableRetired 2025-08-142025-08-14 03:19:4303:21:56 DeviceDeviceID ID LAPO33 LAPO33 Set Set To To AvailableRetired Narawith@Tbs-Marketing.comNarawith@Tbs-Marketing.Com LaptopLaptop. -: F ;<br>Return 2025-08-14 03:18:02 Device ID LAPO33 Returned And Status Set To Available Narawith@Tbs-Marketing.Com Laptop Bank :<br>Receive 2025-08-14 03:17:00 Device ID LAPO33 Received And Assigned To Owner Narawith@Tbs-Marketing.Com Laptop Bank fF)<br>Add Device 2025-08-14 03:15:11 Device ID LAPO33 Was Added To The System Narawith@Tbs-Marketing.Com Laptop - q<br>Update Device 2025-08-14 03:12:03 Device ID MON048 Information Updated Narawith@Tbs-Marketing.Com Monitor Tuk ><br>Receive 2025-08-07 03:18:22 Device ID LAP028 Received And Assigned To Owner Narawith@Tbs-Marketing.Com Laptop Stamp .}<br>Retired 2025-08-07 03:14:18 Device 1D ACCO25 Set To Retired Narawith@Tbs-Marketing.com Accessories -<br>Return 2025-08-07 03:04:55 Device ID ACCO84 Returned And Status Set To Available Narawith@Tbs-Marketing.com Accessories Ethan Ai<br>Return 2025-08-07 03:04:31 Device ID ACCO8S Returned And Status Set To Available Narawith@Tbs-Marketing.com Accessories Ethan A<br>Return 2025-08-07 03:04:07 Device ID LAPO30 Returned And Status Set To Available Narawith@Tbs-Marketing.Com Laptop Magan )<br>Return 2025-08-07 03:03:45 Device ID MONO047 Returned And Status Set To Available Narawith@Tbs-Marketing.Com Monitor Ethan A)<br>Return 2025-08-07 03:02:35 Device ID MON033 Retumed And Status Set To Available Narawith@Tbs-Marketing.Com Monitor Surf =)<br>Return 2025-08-07 02:59:04 Device ID MON044 Retumed And Status Set To Available Narawith@Tbs-Marketing.Com Monitor Ploy r<br>Return 2025-08-07 02:58:46 Device ID ACCO80 Returned And Status Set To Available Narawith@Tbs-Marketing.com Accessories Ploy Al<br>Maintenance 2025-08-07 02:55:23 Device ID ACC004 Set To Maintenance. Narawith@Tbs-Marketing.Com Accessories Bam<br>Add Device 2025-08-07 02:44:19 Device ID LAP032 Was Added To The System Narawith@Tbs-Marketing.Com Laptop - zy<br>Return 2025-08-07 02:42:57 Device ID ACCOB2 Returned And Status Set To Available Narawith@Tbs-Marketing.Com Accessories Megan 7)<br>Return 2025-08-07 02:42:41 Device ID ACCO83 Returned And Status Set To Available Narawith@Tbs-Marketing.Com Accessories Megan "<br>Return 2025-08-07 02:40:36 Device ID MON046 Returned And Status Set To Available Narawith@Tbs-Marketing.Com Monitor Megan =)<br>Return 2025-08-07 02:07:02 Device 1D LAPO31 Returned And Status Set To Available Narawith@Tbs-Marketing.com Laptop Ethan A<br>Receive 2025-08-07 02:06:53 Device ID LAP031 Received And Assigned To Owner Narawith@Tbs-Marketing.Com Laptop Ethan a)<br>Available 2025-08-07 02:06:32 Device ID LAPO31 Set To Available Narawith@Tbs-Marketing.Com Laptop Bank =)<br>Update Device 2025-08-07 02:06:19 Device ID LAPO31 Information Updated Narawith@Tbs-Marketing.com Laptop Bank m<br>Previous | 2 3 m= 24 Next<br><!-- End of picture text -->



<!-- Start of picture text -->
=<br>Stock Supply tossrteting<br>ff Dashboard<br>© Monitor<br>Employees<br>& Laptop<br>Accessories<br># Maintenance Full-Time Tt Intern 2<br>SD History<br>32 Persons 28 Persons<br>& Employees —<br>Add Device<br>6 Logost All Employees<br>60<br>e_<br>Wari Warisa Khanngoen SEO Inter<br>Ethan SEO Intern<br>Hnin PBN Intern<br>Megan SEO Intern<br>Non Graphic Full-Time<br>Pim Graphic Full-Time<br>Kowkong Graphic Full-Time<br>‘Surf ‘SEO Intern<br>Poom PBN Full-Time<br>Nam iT Full-Time<br>Magan SEO Intern<br>Ploy SEM Inter<br>Nerd Content Writer(TH) Full-Time<br>Ruj SEO Full-Time<br>First Content Full-Time<br>Noey SEO Full-Time<br>Bille Sale Full-Time<br>Satang iT Full-Time<br>Bank Janprasert Sutanai It Intern<br>Nui Content Writer(TH) Full-Time<br>Khao SEO & SEM Intern<br>Nongnaphat Content Intem<br>Eve SEO Intern<br>Golf SEM Full-Time<br>Ton-Orr Content Intem<br>Previous 3 | 2 3 + Next<br><!-- End of picture text -->

74 

###### **ประวัติผู้จัดทำโครงงาน** 

###### **1. นายสุธนัย จันทร์ประเสริฐ** 

###### **ระดับการศึกษา:** 

ประกาศนียบัตรวิชาชีพขั้นสูง (ปวส.) 

วิทยาเทคนิคสัตหีบ 

สาขา นักพัฒนาซอฟต์แวร์คอมพิวเตอร์ 

###### **ความถนัด:** 



− HTML 

− CSS 

− JavaScript 

− PHP 

− WordPress 

###### **สถานที่ฝึกงาน/สหกิจศึกษา:** 

บริษัท เดอะ บิสซิเนส เอสอีโอ จำกัด 

###### **สถานที่ติดต่อ:** 

46/126 ม.13 ต.หนองปรือ อ.บางละมุง จ.ชลบุรี 20150 

เบอร์โทรศัพท์ : 083-536-8074 

Email: banksutanai2545@gmail.com 

Line: sutanaibank 

