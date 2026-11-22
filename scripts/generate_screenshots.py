import sqlite3
import os
from PIL import Image, ImageDraw, ImageFont

proj_dir = r"C:\Users\Admin\Railway_Management_System"
screenshot_dir = os.path.join(proj_dir, "screenshots")
os.makedirs(screenshot_dir, exist_ok=True)
db_path = os.path.join(r"C:\Users\Admin\.gemini\antigravity-cli\brain\f7d79dcb-a30e-43e0-bede-1bf1185dd8e4\scratch\railway.db")

conn = sqlite3.connect(db_path)
cur = conn.cursor()

def format_ascii_box(headers, rows):
    if not rows:
        return ["+----------------------+", "| *(No rows returned)* |", "+----------------------+"]
    col_widths = [len(str(h)) for h in headers]
    for row in rows:
        for i, val in enumerate(row):
            val_str = str(val) if val is not None else 'NULL'
            col_widths[i] = max(col_widths[i], len(val_str))
    
    border = '+' + '+'.join('-' * (w + 2) for w in col_widths) + '+'
    header_line = '|' + '|'.join(f" {str(h).ljust(col_widths[i])} " for i, h in enumerate(headers)) + '|'
    
    lines = [border, header_line, border]
    for row in rows:
        row_line = '|' + '|'.join(f" {(str(val) if val is not None else 'NULL').ljust(col_widths[i])} " for i, val in enumerate(row)) + '|'
        lines.append(row_line)
    lines.append(border)
    lines.append(f"({len(rows)} row{'s' if len(rows) != 1 else ''} in set)")
    return lines

def render_linux_terminal(query_num, title, sql_text, ascii_lines, output_img_path):
    font_code = ImageFont.truetype(r"C:\Windows\Fonts\consola.ttf", 15)
    font_bold = ImageFont.truetype(r"C:\Windows\Fonts\consolab.ttf", 15)
    font_ui = ImageFont.truetype(r"C:\Windows\Fonts\segoeui.ttf", 12)
    
    sql_lines = [l for l in sql_text.strip().splitlines() if l.strip()]
    
    max_chars = max(
        max(len(l) for l in sql_lines) + 14,
        max(len(l) for l in ascii_lines) + 6,
        64
    )
    
    char_w = 9.2
    char_h = 21
    pad_x = 20
    pad_y = 14
    title_bar_h = 32
    
    # Lines: bash launch prompt (1) + sql lines + 1 spacer + ascii lines + next prompt (1)
    total_lines = 1 + len(sql_lines) + 1 + len(ascii_lines) + 1
    width = int(max(max_chars * char_w + pad_x * 2, 740))
    height = int(title_bar_h + total_lines * char_h + pad_y * 2)
    
    # Authentic Linux terminal palette (Ubuntu / GNOME dark theme)
    bg_color = (43, 11, 33)       # Ubuntu dark purple-black
    title_bg = (56, 14, 43)       # Slightly lighter header
    border_color = (80, 30, 65)
    
    img = Image.new("RGB", (width, height), bg_color)
    draw = ImageDraw.Draw(img)
    
    # Outer border
    draw.rectangle([(0, 0), (width - 1, height - 1)], outline=border_color, width=1)
    
    # Title bar
    draw.rectangle([(0, 0), (width - 1, title_bar_h)], fill=title_bg)
    draw.line([(0, title_bar_h), (width - 1, title_bar_h)], fill=border_color, width=1)
    
    # Window Title
    term_title = f"admin@ubuntu-srv: ~/railway_db — Query {query_num}"
    draw.text((16, 7), term_title, fill=(225, 225, 225), font=font_ui)
    
    # Linux controls on right: —  □  ✕
    draw.text((width - 72, 6), "—", fill=(180, 180, 180), font=font_ui)
    draw.text((width - 48, 6), "□", fill=(180, 180, 180), font=font_ui)
    draw.text((width - 24, 6), "✕", fill=(240, 110, 110), font=font_ui)
    
    curr_y = title_bar_h + pad_y
    
    # Line 1: Linux bash prompt
    draw.text((pad_x, curr_y), "admin@ubuntu", fill=(74, 246, 38), font=font_bold)
    draw.text((pad_x + 95, curr_y), ":", fill=(255, 255, 255), font=font_code)
    draw.text((pad_x + 104, curr_y), "~/railway_db", fill=(91, 192, 222), font=font_bold)
    draw.text((pad_x + 205, curr_y), "$ sqlite3 railway.db", fill=(245, 245, 245), font=font_code)
    curr_y += char_h
    
    # SQL Query Lines with sqlite prompt
    for i, s_line in enumerate(sql_lines):
        prompt = "sqlite> " if i == 0 else "   ...> "
        draw.text((pad_x, curr_y), prompt, fill=(238, 212, 159), font=font_bold)
        draw.text((pad_x + 65, curr_y), s_line, fill=(245, 169, 127) if s_line.startswith('--') else (255, 255, 255), font=font_code)
        curr_y += char_h
        
    curr_y += 4
    
    # Tabular output lines
    for a_line in ascii_lines:
        if a_line.startswith("+"):
            fill_c = (140, 140, 160)
        elif a_line.startswith("|") and any(k in a_line for k in ["Train", "Station", "PNR", "Booking", "Passenger", "City", "Age", "Payment", "Common"]):
            fill_c = (91, 192, 222) # Cyan header
        elif a_line.startswith("("):
            fill_c = (180, 180, 195) # Summary line
        else:
            fill_c = (74, 246, 38)  # Bright green data rows
        draw.text((pad_x, curr_y), a_line, fill=fill_c, font=font_code)
        curr_y += char_h
        
    # Final ready prompt with cursor block
    draw.text((pad_x, curr_y), "sqlite> ", fill=(238, 212, 159), font=font_bold)
    draw.rectangle([(pad_x + 65, curr_y + 2), (pad_x + 75, curr_y + char_h - 2)], fill=(74, 246, 38))
    
    img.save(output_img_path, "PNG")

queries_data = [
    (1, "Trains with Distance > 1000 km",
     """SELECT TrainNumber, TrainName, TrainType, TotalDistanceKm
FROM Train
WHERE TotalDistanceKm > 1000
ORDER BY TotalDistanceKm DESC;"""),

    (2, "Train Route Terminals (Source & Destination Stations)",
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

    (3, "Total Bookings and Confirmed Revenue per Train",
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

    (4, "Trains with Total Revenue > 5,000",
     """SELECT 
    t.TrainNumber,
    t.TrainName,
    SUM(b.TotalFare) AS TotalRevenue
FROM Train t
INNER JOIN Booking b ON t.TrainID = b.TrainID
WHERE b.BookingStatus = 'Confirmed'
GROUP BY t.TrainID, t.TrainNumber, t.TrainName
HAVING SUM(b.TotalFare) > 5000;"""),

    (5, "Unused / Idle Railway Stations",
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

    (6, "Passengers Traveling on Rajdhani Trains",
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

    (7, "Passengers with Zero Booking Cancellations",
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

    (8, "Trains Longer than Average of Their Category",
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

    (9, "Booking Fares Relative to Average Fare",
     """SELECT 
    b.BookingID,
    b.PNR,
    b.TotalFare,
    ROUND((SELECT AVG(TotalFare) FROM Booking), 2) AS OverallAvgFare,
    ROUND(b.TotalFare - (SELECT AVG(TotalFare) FROM Booking), 2) AS FareDifference
FROM Booking b
ORDER BY b.BookingID
LIMIT 6;"""),

    (10, "Unique Cities with Stations or Passengers (UNION)",
     """SELECT City, 'Station Location' AS SourceType FROM Station
UNION
SELECT City, 'Passenger Residence' AS SourceType FROM Passenger
ORDER BY City;"""),

    (11, "Station Cities with Zero Passenger Residents (EXCEPT)",
     """SELECT City FROM Station
EXCEPT
SELECT City FROM Passenger;"""),

    (12, "Train Ranking by Distance per Train Type (DENSE_RANK)",
     """SELECT 
    TrainType,
    TrainNumber,
    TrainName,
    TotalDistanceKm,
    ROW_NUMBER() OVER (PARTITION BY TrainType ORDER BY TotalDistanceKm DESC) AS RowNum,
    DENSE_RANK() OVER (PARTITION BY TrainType ORDER BY TotalDistanceKm DESC) AS DistRank
FROM Train
ORDER BY TrainType, DistRank;"""),

    (13, "Cumulative Daily Revenue (Running Total)",
     """SELECT 
    PaymentDate,
    SUM(Amount) AS DailyRevenue,
    SUM(SUM(Amount)) OVER (ORDER BY PaymentDate ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS CumulativeRevenue
FROM Payment
WHERE PaymentStatus = 'Success'
GROUP BY PaymentDate
ORDER BY PaymentDate;"""),

    (14, "Trains with at least 3 Scheduled Stops (CTE)",
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

    (15, "Booking Counts by Age Demographics (CASE)",
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

    (16, "Pairs of Trains Sharing Same Origin (Self-Join)",
     """SELECT 
    s.StationName AS CommonSourceStation,
    t1.TrainNumber AS Train_1_Number,
    t1.TrainName AS Train_1_Name,
    t2.TrainNumber AS Train_2_Number,
    t2.TrainName AS Train_2_Name
FROM Train t1
INNER JOIN Train t2 ON t1.SourceStationID = t2.SourceStationID AND t1.TrainID < t2.TrainID
INNER JOIN Station s ON t1.SourceStationID = s.StationID;"""),

    (17, "Gmail Passengers with Phone Starting '98'",
     """SELECT 
    PassengerID,
    UPPER(FirstName || ' ' || LastName) AS FormattedName,
    PhoneNumber,
    Email
FROM Passenger
WHERE Email LIKE '%@gmail.com'
  AND PhoneNumber LIKE '98%'
ORDER BY PassengerID;"""),

    (18, "Confirmed Manifest View for Train 12951",
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

    (19, "Senior Citizen 10% Fare Concession Update",
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

    (20, "Coach Occupancy Percentage and Capacity Utilization",
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

print("Rendering 20 Linux-style screenshots...")
for q_num, title, sql in queries_data:
    statements = [s.strip() for s in sql.split(';') if s.strip()]
    headers = []
    rows = []
    for stmt in statements:
        lines = [l for l in stmt.splitlines() if not l.strip().startswith("--")]
        stmt_clean = "\n".join(lines).strip()
        if not stmt_clean:
            continue
        last_cur = cur.execute(stmt_clean)
        if last_cur.description:
            headers = [d[0] for d in last_cur.description]
            rows = last_cur.fetchall()
            
    ascii_lines = format_ascii_box(headers, rows)
    img_name = f"query_{q_num}.png"
    img_path = os.path.join(screenshot_dir, img_name)
    render_linux_terminal(q_num, title, sql, ascii_lines, img_path)

conn.commit()
conn.close()
print("All 20 Linux-style screenshots generated successfully!")
