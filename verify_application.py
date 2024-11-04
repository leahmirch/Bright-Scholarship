import sqlite3
import sys

# Define minimum eligibility criteria
MIN_GPA = 3.5
MIN_CREDIT_HOURS = 12
MIN_AGE = 18

def verify_application(application_id, bright_db_path='bright_scholarship_db.sqlite', registrar_db_path='registrar_db.sqlite'):
    try:
        # Connect to Bright Scholarship and Registrar databases
        bright_conn = sqlite3.connect(bright_db_path)
        registrar_conn = sqlite3.connect(registrar_db_path)
        bright_cursor = bright_conn.cursor()
        registrar_cursor = registrar_conn.cursor()

        # Fetch application data
        bright_cursor.execute("""
            SELECT id, first_name, last_name, gpa, credit_hours, age
            FROM applications
            WHERE id = ?
        """, (application_id,))
        application = bright_cursor.fetchone()

        if not application:
            print("Application not found.")
            return

        app_id, first_name, last_name, gpa, credit_hours, age = application

        # 1. Check minimum eligibility criteria
        if gpa < MIN_GPA or credit_hours < MIN_CREDIT_HOURS or age < MIN_AGE:
            bright_cursor.execute("UPDATE applications SET status = ? WHERE id = ?", ("ineligible", app_id))
            bright_cursor.execute("""
                INSERT INTO application_status_log (application_id, status, remarks)
                VALUES (?, 'ineligible', 'Does not meet minimum eligibility requirements.')
            """, (app_id,))
            print("Application declined: Does not meet minimum eligibility.")
        
        else:
            # Query registrar for matching student record
            registrar_cursor.execute("""
                SELECT gpa, credit_hours, age
                FROM registrar_records
                WHERE first_name = ? AND last_name = ?
            """, (first_name, last_name))
            registrar_record = registrar_cursor.fetchone()

            if registrar_record:
                reg_gpa, reg_credit_hours, reg_age = registrar_record

                # 2. Check for exact match with registrar data
                if gpa == reg_gpa and credit_hours == reg_credit_hours and age == reg_age:
                    bright_cursor.execute("UPDATE applications SET status = ? WHERE id = ?", ("waiting", app_id))
                    print("Application approved: Status updated to 'waiting'.")
                else:
                    discrepancies = []
                    if gpa != reg_gpa: discrepancies.append("GPA does not match.")
                    if credit_hours != reg_credit_hours: discrepancies.append("Credit hours do not match.")
                    if age != reg_age: discrepancies.append("Age does not match.")

                    bright_cursor.execute("UPDATE applications SET status = ? WHERE id = ?", ("discrepant", app_id))
                    bright_cursor.execute("""
                        INSERT INTO application_status_log (application_id, status, remarks)
                        VALUES (?, 'discrepant', ?)
                    """, (app_id, ", ".join(discrepancies)))
                    print("Application declined: Discrepancies found with registrar data.")
            else:
                bright_cursor.execute("UPDATE applications SET status = ? WHERE id = ?", ("discrepant", app_id))
                bright_cursor.execute("""
                    INSERT INTO application_status_log (application_id, status, remarks)
                    VALUES (?, 'discrepant', 'No matching registrar record found.')
                """, (app_id,))
                print("Application declined: No matching registrar record.")

        # Commit changes and close connections
        bright_conn.commit()
        bright_conn.close()
        registrar_conn.close()

    except sqlite3.Error as e:
        print(f"Database error: {e}")
    except Exception as e:
        print(f"Error: {e}")

# Entry point to run script with an application_id
if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Usage: python verify_application.py <application_id>")
    else:
        application_id = int(sys.argv[1])
        verify_application(application_id)
