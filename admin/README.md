You are an expert full-stack PHP developer with 10+ years experience in Laravel/plain PHP, OpenAI integration, file handling, and academic automation platforms.

I have an existing PHP website (syitech.com.ng) for students doing data analysis projects. I want to add a full AI automation feature exactly as described in the attached PDF "Syitech Automations (95).pdf".

The goal is: Students upload their project Chapters 1-3 (PDF/Word) + Dataset (CSV/Excel), or just the project topic. The system automatically generates Chapters 4 (Data Presentation & Analysis), Chapter 5 (Summary, Conclusion & Recommendations), and the Abstract — with real tables, graphs, interpretations, hypothesis testing, and Word/PDF output.

Follow the EXACT workflow from the PDF:

1. Student Uploads Chapters 1-3 + Dataset
2. Backend reads Methodology from Chapter 3
3. Admin sets AI options on dashboard:
   - Degree Level: NCE/ND, BSc/HND, PGD, MSc/MPhil, PhD
   - Pages: 30, 50, 100
   - Graphs: Yes/No
   - Hypothesis Testing: Yes / Auto-detect
   - Output Format: Word / PDF
4. AI generates Chapters 4, 5 & Abstract
5. Tables → Graphs → Interpretation → Source
6. Word/PDF generated
7. Secure download link
8. Student notified by email
9. Admin final review

Use this EXACT AI system prompt (the "brain" from the PDF):

"You are a Nigerian academic data analyst.
Use Chapter 3 methodology to analyze the dataset.

Generate:
- Chapter 4: Data Presentation & Analysis
- Chapter 5: Summary, Conclusion & Recommendations
- Abstract

Rules:
1. Every table must have: Title, Frequency & Percentage, Source: Fieldwork, 2025, Detailed interpretation in paragraph form.
2. Where applicable, generate graphs and output the exact data points in JSON format like {\"chart_type\":\"bar\",\"data\":{\"labels\":[...],\"values\":[...]}} so PHP can create real charts.
3. Use formal academic tone (Nigerian universities standard).
4. Detect and test hypotheses automatically using the stated method.
5. Total length: [pages from admin setting] pages.

Output in clean Markdown with clear headings, tables, and JSON graph blocks."

Technical requirements for my existing PHP site (PHP 8.1+, MySQL):

- Use Composer libraries:
  - openai-php/client
  - smalot/pdfparser
  - phpoffice/phpword
  - phpoffice/phpspreadsheet
  - mpdf/mpdf
  - vlucas/phpdotenv
  - phpmailer/phpmailer

- Create these files (give me complete, ready-to-use code for each):
  1. upload.php (student upload form handler — extracts text from Chapters 1-3 and dataset summary)
  2. admin_settings.php (dashboard form with checkboxes/selects exactly as PDF page 4)
  3. generate.php (builds full prompt, calls OpenAI GPT-4o with temperature 0.2, parses output, creates real DOCX with tables + embedded charts using PHPWord, or PDF with mPDF)
  4. download.php (secure download link)
  5. database.sql (new table structure for student_jobs)

- Store files in /uploads/ folder with unique IDs
- Generate download link like: https://syitech.com.ng/downloads/student123-analysis.docx
- Send email notification to student
- Keep admin review queue (status = ready → reviewed)

- Make everything undetectable as AI: low temperature, Nigerian academic style, no AI buzzwords.

- Handle both "upload full chapters + dataset" and simple "project topic only" mode (if topic only, generate placeholder Chapters 1-3 outline first).

- Security: validate uploads, store API key in .env, limit file size, prevent direct access to generated files.

Provide the code in this exact order:
1. Full database.sql
2. .env example
3. upload.php (complete)
4. admin_settings.php (complete)
5. generate.php (complete with OpenAI call and PHPWord chart insertion)
6. download.php
7. How to integrate these files into my existing student dashboard and admin panel.

Add comments in the code explaining where each part matches the PDF. Make the code production-ready, clean, and modular so I can drop it into my current website without conflicts.

Start your response with "✅ INTEGRATION READY — Here is the complete PHP code for your Syitech AI automation system" and then give all files one by one.