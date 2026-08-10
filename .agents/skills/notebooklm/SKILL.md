---
name: notebooklm
description: Query, search, ingest sources, and manage notebooks using Google NotebookLM (Gemini 2.5) via MCP.
---

# NotebookLM Agent Skill

Skill สำหรับใช้งาน **Google NotebookLM** ผ่าน MCP Server (NotebookLM MCP v2.0) เพื่อถามตอบ ค้นหาข้อมูล สรุปเนื้อหา เพิ่มซอร์สข้อมูล และสร้าง Audio Overview

## การตั้งค่าและการติดตั้ง (Setup & Configuration)

- **การเปิดใช้งาน MCP Server**:
  - สั่งรัน MCP Server ผ่าน stdio: `npx notebooklm-mcp@latest` หรือ `node .agent/skills/notebooklm/dist/index.js`
  - ข้อมูลการเข้าสู่ระบบถูกบันทึกไว้เรียบร้อยแล้วที่ `%APPDATA%\notebooklm-mcp\Data\`

## เครื่องมือที่มีให้ใช้งาน (Tools Reference)

1. **`ask_question`**: ถามคำถามกับ NotebookLM โดยระบุคำถาม `question` และ `session_id` (ถ้ามี)
2. **`list_notebooks`**: แสดงรายการ สมุดโน้ต (Notebooks) ทั้งหมดใน Library
3. **`add_notebook`**: บันทึก URL ของ NotebookLM เข้าสู่ Library
4. **`select_notebook`**: เลือกสมุดโน้ตที่จะใช้เป็นค่าเริ่มต้นในการถามคำถาม
5. **`add_source`**: เพิ่มแหล่งข้อมูล (URL หรือ Text) เข้าไปใน NotebookLM
6. **`generate_audio`**: สร้างคลิปเสียงสรุปเนื้อหา (Audio Overview)
7. **`download_audio`**: ดาวน์โหลดไฟล์เสียงสรุปเนื้อหาลงเครื่อง
8. **`get_health`**: ตรวจสอบสถานะการเชื่อมต่อและการล็อกอิน

## ตัวอย่างการใช้งาน (Usage Examples)

- **ถามคำถามจาก Notebook**:
  "ช่วยค้นหาข้อมูลเรื่องสรุปสต็อกสินค้าจาก NotebookLM ให้หน่อย"
- **เพิ่มซอร์สข้อมูล**:
  "ช่วยนำเนื้อหาข้อความนี้/URL นี้ไปเพิ่มเป็น Source ใน NotebookLM"
- **สร้าง Audio Overview**:
  "ช่วยสร้างไฟล์เสียงสรุปเนื้อหา Audio Overview จาก NotebookLM ให้หน่อย"
