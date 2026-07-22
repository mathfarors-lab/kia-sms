import pandas as pd
import math

filePath = "document/Student's Data June 2026.xlsx"
df = pd.read_excel(filePath, sheet_name="June2026")

current_class_header = None
students_by_class = {}
current_students = []

for idx, row in df.iterrows():
    val = row['Unnamed: 0']
    
    # If the value is a class header
    if pd.notna(val) and not isinstance(val, (int, float)) and not str(val).strip().isdigit():
        if current_class_header:
            students_by_class[current_class_header] = current_students
        current_class_header = str(val).strip()
        current_students = []
    else:
        # Check if we have student data
        name_en = row.get("Student's Name in English ")
        if pd.notna(name_en) and str(name_en).strip() != "":
            current_students.append({
                'row_index': idx,
                'name_en': str(name_en).strip(),
                'name_km': str(row.get("Student's Name in Khmer", '')) if pd.notna(row.get("Student's Name in Khmer")) else '',
                'gender': str(row.get("Sex", 'male')).strip().lower(),
                'dob': str(row.get("Date Of Birth", '')) if pd.notna(row.get("Date Of Birth")) else '',
                'phone': str(row.get("Phone's Number", '')) if pd.notna(row.get("Phone's Number")) else '',
                'class': str(row.get("Class", '')) if pd.notna(row.get("Class")) else '',
                'program': str(row.get("Program", '')) if pd.notna(row.get("Program")) else '',
                'sub_program': str(row.get("Sub-Program", '')) if pd.notna(row.get("Sub-Program")) else '',
                'scholarship': str(row.get("Scholarship", '')) if pd.notna(row.get("Scholarship")) else '',
            })

if current_class_header:
    students_by_class[current_class_header] = current_students

print("Total classes found:", len(students_by_class))
total_students = 0
for header, sts in list(students_by_class.items())[:15]:
    print(f"\nHeader: {header}")
    print(f"Number of students: {len(sts)}")
    total_students += len(sts)
    if len(sts) > 0:
        print("Sample student:", sts[0])
print(f"\nTotal students in first 15 classes: {total_students}")
