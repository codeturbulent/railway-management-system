import os
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from docx.oxml import parse_xml
from docx.oxml.ns import nsdecls

proj_dir = r"C:\Users\Admin\Railway_Management_System"
screenshot_dir = os.path.join(proj_dir, "screenshots")
doc_path = os.path.join(proj_dir, "Railway_Management_System.docx")
erd_img_path = os.path.join(proj_dir, "ERD.png")

doc = Document()

# Page Margins (0.8 inches for clean layout)
for section in doc.sections:
    section.top_margin = Inches(0.8)
    section.bottom_margin = Inches(0.8)
    section.left_margin = Inches(0.8)
    section.right_margin = Inches(0.8)

def set_cell_background(cell, fill_hex):
    tcPr = cell._tc.get_or_add_tcPr()
    shd = parse_xml(f'<w:shd {nsdecls("w")} w:fill="{fill_hex}"/>')
    tcPr.append(shd)

def add_code_box(doc, text):
    table = doc.add_table(rows=1, cols=1)
    table.alignment = WD_TABLE_ALIGNMENT.CENTER
    cell = table.cell(0, 0)
    cell.width = Inches(6.8)
    set_cell_background(cell, "F3F4F6")
    
    tcPr = cell._tc.get_or_add_tcPr()
    borders = parse_xml(f'<w:tcBorders {nsdecls("w")}><w:top w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/><w:left w:val="single" w:sz="14" w:space="0" w:color="2B0B21"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/><w:right w:val="single" w:sz="4" w:space="0" w:color="D1D5DB"/></w:tcBorders>')
    tcPr.append(borders)
    
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(3)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.15
    run = p.add_run(text.strip())
    run.font.name = "Consolas"
    run.font.size = Pt(9.5)
    run.font.color.rgb = RGBColor(30, 30, 30)

# Title Header
p_title = doc.add_paragraph()
p_title.paragraph_format.space_before = Pt(12)
p_title.paragraph_format.space_after = Pt(4)
p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
t_run = p_title.add_run("RAILWAY MANAGEMENT SYSTEM (RMS)")
t_run.font.name = "Segoe UI"
t_run.font.size = Pt(22)
t_run.font.bold = True
t_run.font.color.rgb = RGBColor(43, 11, 33)

p_sub = doc.add_paragraph()
p_sub.paragraph_format.space_before = Pt(0)
p_sub.paragraph_format.space_after = Pt(16)
p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
s_run = p_sub.add_run("Database Project: ER Diagram, Relational Schemas, SQL Queries with Screenshots, and Relational Algebra")
s_run.font.name = "Segoe UI"
s_run.font.size = Pt(11)
s_run.font.italic = True
s_run.font.color.rgb = RGBColor(100, 100, 100)

# Compact Metadata Box
meta_table = doc.add_table(rows=2, cols=2)
meta_table.alignment = WD_TABLE_ALIGNMENT.CENTER
meta_data = [
    ("Database Topic", "Railway Management System (RMS)"),
    ("Normalization", "3NF / BCNF Normalized Schema"),
    ("Target Engines", "SQLite 3.45+ / MySQL 8.0+ / PostgreSQL 14+"),
    ("Deliverables", "PlantUML ERD, Relational Schema, 20 Queries + Linux Screenshots, Relational Algebra")
]
for idx, (label, val) in enumerate(meta_data):
    r_idx = idx // 2
    c_idx = idx % 2
    cell = meta_table.rows[r_idx].cells[c_idx]
    cell.width = Inches(3.4)
    set_cell_background(cell, "F9FAFB")
    p = cell.paragraphs[0]
    p.paragraph_format.space_before = Pt(2)
    p.paragraph_format.space_after = Pt(2)
    r_lbl = p.add_run(f"{label}: ")
    r_lbl.font.bold = True
    r_lbl.font.size = Pt(9.5)
    r_lbl.font.name = "Segoe UI"
    r_val = p.add_run(val)
    r_val.font.size = Pt(9.5)
    r_val.font.name = "Segoe UI"

p_div = doc.add_paragraph()
p_div.paragraph_format.space_before = Pt(10)
p_div.paragraph_format.space_after = Pt(10)

# Section 1: ER Diagram
h1 = doc.add_heading("1. Entity-Relationship (ER) Diagram", level=1)
h1.style.font.name = "Segoe UI"
h1.style.font.color.rgb = RGBColor(43, 11, 33)

if os.path.exists(erd_img_path):
    p_img = doc.add_paragraph()
    p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_img.paragraph_format.space_before = Pt(6)
    p_img.paragraph_format.space_after = Pt(4)
    r_img = p_img.add_run()
    r_img.add_picture(erd_img_path, width=Inches(5.6))
    
    p_cap = doc.add_paragraph()
    p_cap.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_cap.paragraph_format.space_after = Pt(12)
    c_run = p_cap.add_run("Figure 1: Crow's Foot ER Diagram (Station, Train, TrainSchedule, Coach, Passenger, Booking, SeatReservation, Payment)")
    c_run.font.italic = True
    c_run.font.size = Pt(9)
    c_run.font.color.rgb = RGBColor(100, 100, 100)

# Section 2: Relational Schemas
h2 = doc.add_heading("2. Relational Schemas (3NF / BCNF)", level=1)
h2.style.font.name = "Segoe UI"
h2.style.font.color.rgb = RGBColor(43, 11, 33)

schema_text = (
    "• Station (StationID [PK], StationCode [UQ], StationName, City, State, PlatformsCount)\n"
    "• Train (TrainID [PK], TrainNumber [UQ], TrainName, TrainType, SourceStationID [FK], DestinationStationID [FK], TotalDistanceKm)\n"
    "• TrainSchedule (ScheduleID [PK], TrainID [FK], StationID [FK], StopNumber, ArrivalTime, DepartureTime, DistanceFromSourceKm)\n"
    "• Coach (CoachID [PK], TrainID [FK], CoachNumber, CoachType, TotalSeats)\n"
    "• Passenger (PassengerID [PK], FirstName, LastName, Gender, Age, PhoneNumber [UQ], Email [UQ], City)\n"
    "• Booking (BookingID [PK], PNR [UQ], PassengerID [FK], TrainID [FK], JourneyDate, BookingDate, TotalFare, BookingStatus)\n"
    "• SeatReservation (ReservationID [PK], BookingID [FK], CoachID [FK], SeatNumber, BerthType, Fare)\n"
    "• Payment (PaymentID [PK], BookingID [FK], PaymentDate, Amount, PaymentMethod, PaymentStatus)"
)
add_code_box(doc, schema_text)

# Table summary of constraints
doc.add_paragraph().paragraph_format.space_after = Pt(4)
t_summary = doc.add_table(rows=9, cols=4)
t_summary.alignment = WD_TABLE_ALIGNMENT.CENTER
t_data = [
    ("Table Name", "Primary Key", "Foreign Keys", "Key Constraints"),
    ("Station", "StationID", "None", "StationCode UNIQUE, PlatformsCount > 0"),
    ("Train", "TrainID", "SourceStationID, DestinationStationID", "TrainNumber UNIQUE, TotalDistanceKm > 0"),
    ("TrainSchedule", "ScheduleID", "TrainID, StationID", "UNIQUE(TrainID, StopNumber)"),
    ("Coach", "CoachID", "TrainID", "UNIQUE(TrainID, CoachNumber)"),
    ("Passenger", "PassengerID", "None", "Phone & Email UNIQUE, Age BETWEEN 0 AND 120"),
    ("Booking", "BookingID", "PassengerID, TrainID", "PNR UNIQUE, TotalFare >= 0"),
    ("SeatReservation", "ReservationID", "BookingID, CoachID", "UNIQUE(CoachID, SeatNumber)"),
    ("Payment", "PaymentID", "BookingID", "Amount >= 0, Status: Success/Failed/Refunded")
]
for idx, row_data in enumerate(t_data):
    row = t_summary.rows[idx]
    for c_i, val in enumerate(row_data):
        cell = row.cells[c_i]
        p = cell.paragraphs[0]
        p.paragraph_format.space_before = Pt(2)
        p.paragraph_format.space_after = Pt(2)
        r = p.add_run(val)
        r.font.name = "Calibri"
        r.font.size = Pt(8.5)
        if idx == 0:
            set_cell_background(cell, "2B0B21")
            r.font.bold = True
            r.font.color.rgb = RGBColor(255, 255, 255)
        else:
            set_cell_background(cell, "F9FAFB" if idx % 2 == 0 else "FFFFFF")

doc.add_page_break()

# Section 3: SQL Queries & Linux Screenshots
h3 = doc.add_heading("3. SQL Queries & Output Screenshots", level=1)
h3.style.font.name = "Segoe UI"
h3.style.font.color.rgb = RGBColor(43, 11, 33)

queries_list = [
    (1, "Filter & Sort: Trains with Distance > 1000 km",
     """SELECT TrainNumber, TrainName, TrainType, TotalDistanceKm
FROM Train
WHERE TotalDistanceKm > 1000
ORDER BY TotalDistanceKm DESC;"""),

    (2, "Inner Join: Train Terminals (Source & Destination)",
     """SELECT 
    t.TrainNumber,
    t.TrainName,
    t.TrainType,
    s_src.StationName AS SourceStation,
    s_dst.StationName AS DestinationStation,
    t.TotalDistanceKm
FROM Train t
INNER JOIN Station s_src ON t.SourceStationID = s_src.StationID
INNER JOIN Station s_dst ON t.DestinationStationID = s_dst.StationID
ORDER BY t.TrainNumber;"""),

    (3, "Aggregation (GROUP BY): Revenue & Bookings per Train",
     """SELECT 
    t.TrainNumber,
    t.TrainName,
    COUNT(b.BookingID) AS TotalBookings,
    SUM(b.TotalFare) AS ConfirmedRevenue
FROM Train t
INNER JOIN Booking b ON t.TrainID = b.TrainID
WHERE b.BookingStatus = 'Confirmed'
GROUP BY t.TrainID, t.TrainNumber, t.TrainName
ORDER BY ConfirmedRevenue DESC;"""),

    (4, "Group Filter (HAVING): Trains with Revenue > 5,000",
     """SELECT 
    t.TrainNumber,
    t.TrainName,
    SUM(b.TotalFare) AS TotalRevenue
FROM Train t
INNER JOIN Booking b ON t.TrainID = b.TrainID
WHERE b.BookingStatus = 'Confirmed'
GROUP BY t.TrainID, t.TrainNumber, t.TrainName
HAVING SUM(b.TotalFare) > 5000;"""),

    (5, "Left Outer Join: Stations with No Origin/Terminus Trains",
     """SELECT 
    s.StationID,
    s.StationCode,
    s.StationName,
    s.City,
    s.State
FROM Station s
LEFT JOIN Train t_src ON s.StationID = t_src.SourceStationID
LEFT JOIN Train t_dst ON s.StationID = t_dst.DestinationStationID
WHERE t_src.TrainID IS NULL AND t_dst.TrainID IS NULL;"""),

    (6, "Subquery (IN): Passengers Booked on Rajdhani Class",
     """SELECT 
    PassengerID,
    FirstName || ' ' || LastName AS FullName,
    PhoneNumber,
    City
FROM Passenger
WHERE PassengerID IN (
    SELECT DISTINCT b.PassengerID
    FROM Booking b
    INNER JOIN Train t ON b.TrainID = t.TrainID
    WHERE t.TrainType = 'Rajdhani'
)
ORDER BY PassengerID;"""),

    (7, "Correlated Subquery (NOT EXISTS): Zero Cancellation Passengers",
     """SELECT 
    p.PassengerID,
    p.FirstName || ' ' || LastName AS FullName,
    p.Email,
    p.City
FROM Passenger p
WHERE EXISTS (
    SELECT 1 FROM Booking b WHERE b.PassengerID = p.PassengerID
)
AND NOT EXISTS (
    SELECT 1 FROM Booking b WHERE b.PassengerID = p.PassengerID AND b.BookingStatus = 'Cancelled'
)
ORDER BY p.PassengerID;"""),

    (8, "Correlated Subquery: Trains Above Category Average Distance",
     """SELECT 
    t1.TrainNumber,
    t1.TrainName,
    t1.TrainType,
    t1.TotalDistanceKm
FROM Train t1
WHERE t1.TotalDistanceKm > (
    SELECT AVG(t2.TotalDistanceKm)
    FROM Train t2
    WHERE t2.TrainType = t1.TrainType
);"""),

    (9, "Scalar Subquery: Ticket Fare Variance from Average",
     """SELECT 
    b.BookingID,
    b.PNR,
    b.TotalFare,
    ROUND((SELECT AVG(TotalFare) FROM Booking), 2) AS OverallAvgFare,
    ROUND(b.TotalFare - (SELECT AVG(TotalFare) FROM Booking), 2) AS FareDifference
FROM Booking b
ORDER BY b.BookingID
LIMIT 6;"""),

    (10, "Set Operation (UNION): Distinct Station and Passenger Cities",
     """SELECT City, 'Station Location' AS SourceType FROM Station
UNION
SELECT City, 'Passenger Residence' AS SourceType FROM Passenger
ORDER BY City;"""),

    (11, "Set Operation (EXCEPT): Station Cities without Passengers",
     """SELECT City FROM Station
EXCEPT
SELECT City FROM Passenger;"""),

    (12, "Window Function (DENSE_RANK): Train Distance Rank per Type",
     """SELECT 
    TrainType,
    TrainNumber,
    TrainName,
    TotalDistanceKm,
    ROW_NUMBER() OVER (PARTITION BY TrainType ORDER BY TotalDistanceKm DESC) AS RowNum,
    DENSE_RANK() OVER (PARTITION BY TrainType ORDER BY TotalDistanceKm DESC) AS DistRank
FROM Train
ORDER BY TrainType, DistRank;"""),

    (13, "Window Function (SUM OVER): Running Cumulative Revenue",
     """SELECT 
    PaymentDate,
    SUM(Amount) AS DailyRevenue,
    SUM(SUM(Amount)) OVER (ORDER BY PaymentDate ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS CumulativeRevenue
FROM Payment
WHERE PaymentStatus = 'Success'
GROUP BY PaymentDate
ORDER BY PaymentDate;"""),

    (14, "Common Table Expression (CTE): Trains with >= 3 Stops",
     """WITH TrainStopCounts AS (
    SELECT 
        ts.TrainID,
        t.TrainNumber,
        t.TrainName,
        COUNT(ts.StationID) AS TotalStops
    FROM TrainSchedule ts
    INNER JOIN Train t ON ts.TrainID = t.TrainID
    GROUP BY ts.TrainID, t.TrainNumber, t.TrainName
)
SELECT 
    TrainNumber,
    TrainName,
    TotalStops
FROM TrainStopCounts
WHERE TotalStops >= 3
ORDER BY TotalStops DESC;"""),

    (15, "Conditional Logic (CASE): Bookings by Age Demographics",
     """SELECT 
    CASE 
        WHEN p.Age >= 60 THEN 'Senior Citizen (60+)'
        WHEN p.Age >= 25 THEN 'Adult (25-59)'
        ELSE 'Youth (<25)'
    END AS AgeGroup,
    COUNT(b.BookingID) AS TotalBookings,
    ROUND(SUM(b.TotalFare), 2) AS TotalSpent
FROM Passenger p
INNER JOIN Booking b ON p.PassengerID = b.PassengerID
GROUP BY AgeGroup
ORDER BY TotalSpent DESC;"""),

    (16, "Self-Join: Pairs of Trains Sharing Common Source Station",
     """SELECT 
    s.StationName AS CommonSourceStation,
    t1.TrainNumber AS Train_1_Number,
    t1.TrainName AS Train_1_Name,
    t2.TrainNumber AS Train_2_Number,
    t2.TrainName AS Train_2_Name
FROM Train t1
INNER JOIN Train t2 ON t1.SourceStationID = t2.SourceStationID AND t1.TrainID < t2.TrainID
INNER JOIN Station s ON t1.SourceStationID = s.StationID;"""),

    (17, "String Matching: Gmail Passengers with Contact Starting 98",
     """SELECT 
    PassengerID,
    UPPER(FirstName || ' ' || LastName) AS FormattedName,
    PhoneNumber,
    Email
FROM Passenger
WHERE Email LIKE '%@gmail.com'
  AND PhoneNumber LIKE '98%'
ORDER BY PassengerID;"""),

    (18, "Database View: Confirmed Passenger Manifest for Train 12951",
     """DROP VIEW IF EXISTS v_ConfirmedManifest;
CREATE VIEW v_ConfirmedManifest AS
SELECT 
    b.PNR,
    p.FirstName || ' ' || p.LastName AS PassengerName,
    t.TrainNumber,
    t.TrainName,
    c.CoachNumber,
    c.CoachType,
    sr.SeatNumber,
    sr.BerthType,
    sr.Fare
FROM Booking b
INNER JOIN Passenger p ON b.PassengerID = p.PassengerID
INNER JOIN Train t ON b.TrainID = t.TrainID
INNER JOIN SeatReservation sr ON b.BookingID = sr.BookingID
INNER JOIN Coach c ON sr.CoachID = c.CoachID
WHERE b.BookingStatus = 'Confirmed';

SELECT PNR, PassengerName, CoachNumber, CoachType, SeatNumber, BerthType, Fare
FROM v_ConfirmedManifest
WHERE TrainNumber = '12951'
ORDER BY SeatNumber;"""),

    (19, "Data Modification: Senior Citizen 10% Fare Concession Update",
     """UPDATE Booking
SET TotalFare = ROUND(TotalFare * 0.90, 2)
WHERE BookingStatus = 'Confirmed'
  AND PassengerID IN (SELECT PassengerID FROM Passenger WHERE Age >= 60);

SELECT 
    b.BookingID,
    b.PNR,
    p.FirstName || ' ' || p.LastName AS PassengerName,
    p.Age,
    b.TotalFare AS DiscountedFare,
    b.BookingStatus
FROM Booking b
INNER JOIN Passenger p ON b.PassengerID = p.PassengerID
WHERE p.Age >= 60;"""),

    (20, "Analytical Query: Coach Capacity & Occupancy Rate",
     """SELECT 
    t.TrainNumber,
    t.TrainName,
    c.CoachNumber,
    c.CoachType,
    c.TotalSeats AS CoachCapacity,
    COUNT(sr.ReservationID) AS SeatsReserved,
    ROUND((COUNT(sr.ReservationID) * 100.0) / c.TotalSeats, 2) AS OccupancyPercentage
FROM Coach c
INNER JOIN Train t ON c.TrainID = t.TrainID
LEFT JOIN SeatReservation sr ON c.CoachID = sr.CoachID
GROUP BY t.TrainID, t.TrainNumber, t.TrainName, c.CoachID, c.CoachNumber, c.CoachType, c.TotalSeats
ORDER BY t.TrainNumber, c.CoachNumber;""")
]

for q_num, title, sql in queries_list:
    hq = doc.add_heading(f"Query {q_num}: {title}", level=2)
    hq.style.font.name = "Segoe UI"
    hq.style.font.color.rgb = RGBColor(43, 11, 33)
    hq.paragraph_format.space_before = Pt(8)
    hq.paragraph_format.space_after = Pt(2)
    
    add_code_box(doc, sql)
    
    img_name = f"query_{q_num}.png"
    img_path = os.path.join(screenshot_dir, img_name)
    if os.path.exists(img_path):
        p_img = doc.add_paragraph()
        p_img.alignment = WD_ALIGN_PARAGRAPH.CENTER
        p_img.paragraph_format.space_before = Pt(4)
        p_img.paragraph_format.space_after = Pt(12)
        r_img = p_img.add_run()
        r_img.add_picture(img_path, width=Inches(6.6))

doc.add_page_break()

# Section 4: Relational Algebra Queries (Compact)
h4 = doc.add_heading("4. Relational Algebra Queries", level=1)
h4.style.font.name = "Segoe UI"
h4.style.font.color.rgb = RGBColor(43, 11, 33)

ra_data = [
    ("RA 1: Selection & Projection (Distance > 1000 km)",
     "π[TrainNumber, TrainName]( σ[TotalDistanceKm > 1000](Train) )"),

    ("RA 2: Theta Join (Train Terminals)",
     "Temp1 ← Train ⋈[Train.SourceStationID = S1.StationID] (ρ[S1](Station))\n"
     "Temp2 ← Temp1 ⋈[Train.DestinationStationID = S2.StationID] (ρ[S2](Station))\n"
     "Result ← π[TrainNumber, TrainName, S1.StationName, S2.StationName](Temp2)"),

    ("RA 3: Grouping & Aggregation (Revenue per Train)",
     "Temp1 ← σ[BookingStatus = 'Confirmed'](Booking)\n"
     "Temp2 ← Train ⋈[Train.TrainID = Temp1.TrainID] Temp1\n"
     "Result ← [TrainNumber, TrainName] G [COUNT(BookingID) → TotalBookings, SUM(TotalFare) → ConfirmedRevenue](Temp2)"),

    ("RA 4: Set Union (Station Cities ∪ Passenger Cities)",
     "Result ← π[City](Station) ∪ π[City](Passenger)"),

    ("RA 5: Set Difference (Station Cities - Passenger Cities)",
     "Result ← π[City](Station) - π[City](Passenger)"),

    ("RA 6: Semi-Join (Passengers on Rajdhani Trains)",
     "RajdhaniTrains ← σ[TrainType = 'Rajdhani'](Train)\n"
     "RajdhaniBookings ← Booking ⋈ RajdhaniTrains\n"
     "RajdhaniPassengers ← π[PassengerID](RajdhaniBookings)\n"
     "Result ← π[FirstName, LastName, PhoneNumber](Passenger ⋈ RajdhaniPassengers)"),

    ("RA 7: Anti-Semi-Join (Passengers with Zero Cancellations)",
     "AllBookings ← π[PassengerID](Booking)\n"
     "Cancelled ← π[PassengerID]( σ[BookingStatus = 'Cancelled'](Booking) )\n"
     "Result ← π[PassengerID, FirstName, LastName](Passenger ⋈ (AllBookings - Cancelled))"),

    ("RA 8: Self-Join (Train Pairs with Same Source Station)",
     "T1 ← ρ[T1](Train),  T2 ← ρ[T2](Train)\n"
     "Pairs ← σ[T1.SourceStationID = T2.SourceStationID ∧ T1.TrainID < T2.TrainID](T1 × T2)\n"
     "Result ← π[T1.TrainNumber, T1.TrainName, T2.TrainNumber, T2.TrainName](Pairs)"),

    ("RA 9: Left Outer Join Equivalent (Unused Stations)",
     "SourceIDs ← π[SourceStationID](Train)\n"
     "Result ← π[StationName](Station - ρ[StationID ← SourceStationID](SourceIDs))"),

    ("RA 10: Relational Division (Passengers Booked on ALL NDLS Trains)",
     "NDLSTrains ← π[TrainID]( σ[SourceStationID = 1](Train) )\n"
     "PassengerBookings ← π[PassengerID, TrainID](Booking)\n"
     "QualifiedPassengers ← PassengerBookings ÷ NDLSTrains\n"
     "Result ← π[FirstName, LastName](Passenger ⋈ QualifiedPassengers)")
]

for idx, (title, expr) in enumerate(ra_data, 1):
    p_t = doc.add_paragraph()
    p_t.paragraph_format.space_before = Pt(6)
    p_t.paragraph_format.space_after = Pt(2)
    r = p_t.add_run(f"{idx}. {title}")
    r.font.bold = True
    r.font.size = Pt(10.5)
    r.font.color.rgb = RGBColor(43, 11, 33)
    add_code_box(doc, expr)

# Section 5: Reproduction Commands (Minimal)
doc.add_page_break()
h5 = doc.add_heading("5. Setup & Execution Commands", level=1)
h5.style.font.name = "Segoe UI"
h5.style.font.color.rgb = RGBColor(43, 11, 33)

bash_cmds = (
    "# 1. Initialize and populate database in Linux terminal\n"
    "sqlite3 railway.db < railway_management_system.sql\n\n"
    "# 2. Run query file\n"
    "sqlite3 -header -column railway.db < queries.sql\n\n"
    "# 3. Execute individual queries\n"
    "sqlite3 -header -column railway.db \"SELECT * FROM Train WHERE TotalDistanceKm > 1000;\""
)
add_code_box(doc, bash_cmds)

doc.save(doc_path)
print(f"Concise, unbloated document saved successfully at: {doc_path}")
