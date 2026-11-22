-- =============================================================================
-- RAILWAY MANAGEMENT SYSTEM - 20 VERIFIED SQL QUERIES
-- =============================================================================

-- Query 1: Basic Selection, Filtering, and Sorting
-- Retrieve all trains covering more than 1000 km, sorted by distance in descending order.
SELECT TrainNumber, TrainName, TrainType, TotalDistanceKm
FROM Train
WHERE TotalDistanceKm > 1000
ORDER BY TotalDistanceKm DESC;

-- Query 2: Multi-Table Inner Join
-- Display train details along with origin station name and destination station name.
SELECT 
    t.TrainNumber,
    t.TrainName,
    t.TrainType,
    s_src.StationName AS SourceStation,
    s_dst.StationName AS DestinationStation,
    t.TotalDistanceKm
FROM Train t
INNER JOIN Station s_src ON t.SourceStationID = s_src.StationID
INNER JOIN Station s_dst ON t.DestinationStationID = s_dst.StationID
ORDER BY t.TrainNumber;

-- Query 3: Aggregation with GROUP BY
-- Calculate total bookings and total confirmed revenue per train.
SELECT 
    t.TrainNumber,
    t.TrainName,
    COUNT(b.BookingID) AS TotalBookings,
    SUM(b.TotalFare) AS ConfirmedRevenue
FROM Train t
INNER JOIN Booking b ON t.TrainID = b.TrainID
WHERE b.BookingStatus = 'Confirmed'
GROUP BY t.TrainID, t.TrainNumber, t.TrainName
ORDER BY ConfirmedRevenue DESC;

-- Query 4: Group Filtering with HAVING
-- Find all trains that have generated more than 5,000 in total confirmed revenue.
SELECT 
    t.TrainNumber,
    t.TrainName,
    SUM(b.TotalFare) AS TotalRevenue
FROM Train t
INNER JOIN Booking b ON t.TrainID = b.TrainID
WHERE b.BookingStatus = 'Confirmed'
GROUP BY t.TrainID, t.TrainNumber, t.TrainName
HAVING SUM(b.TotalFare) > 5000;

-- Query 5: Left Outer Join for Missing/Orphan Records
-- Identify stations that are currently not used as either a source or destination by any train.
SELECT 
    s.StationID,
    s.StationCode,
    s.StationName,
    s.City,
    s.State
FROM Station s
LEFT JOIN Train t_src ON s.StationID = t_src.SourceStationID
LEFT JOIN Train t_dst ON s.StationID = t_dst.DestinationStationID
WHERE t_src.TrainID IS NULL AND t_dst.TrainID IS NULL;

-- Query 6: Subquery with IN
-- List passengers who have booked tickets on 'Rajdhani' category trains.
SELECT 
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
ORDER BY PassengerID;

-- Query 7: Subquery with NOT EXISTS
-- Find passengers who have made at least one booking and have NEVER cancelled any booking.
SELECT 
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
ORDER BY p.PassengerID;

-- Query 8: Correlated Subquery
-- List trains whose distance is greater than the average distance of all trains of the same TrainType.
SELECT 
    t1.TrainNumber,
    t1.TrainName,
    t1.TrainType,
    t1.TotalDistanceKm
FROM Train t1
WHERE t1.TotalDistanceKm > (
    SELECT AVG(t2.TotalDistanceKm)
    FROM Train t2
    WHERE t2.TrainType = t1.TrainType
);

-- Query 9: Scalar Subquery in SELECT
-- Display each booking with its fare and the difference from the overall average booking fare.
SELECT 
    b.BookingID,
    b.PNR,
    b.TotalFare,
    ROUND((SELECT AVG(TotalFare) FROM Booking), 2) AS OverallAvgFare,
    ROUND(b.TotalFare - (SELECT AVG(TotalFare) FROM Booking), 2) AS FareDifference
FROM Booking b
ORDER BY b.BookingID
LIMIT 8;

-- Query 10: Set Operation (UNION)
-- List all unique cities that either have a railway station OR are the hometown of a passenger.
SELECT City, 'Station Location' AS SourceType FROM Station
UNION
SELECT City, 'Passenger Residence' AS SourceType FROM Passenger
ORDER BY City;

-- Query 11: Set Operation (EXCEPT / Difference)
-- Find cities that have railway stations but have no registered passenger living in them.
SELECT City FROM Station
EXCEPT
SELECT City FROM Passenger;

-- Query 12: Window Function (DENSE_RANK & ROW_NUMBER)
-- Rank trains by distance within each TrainType category.
SELECT 
    TrainType,
    TrainNumber,
    TrainName,
    TotalDistanceKm,
    ROW_NUMBER() OVER (PARTITION BY TrainType ORDER BY TotalDistanceKm DESC) AS RowNum,
    DENSE_RANK() OVER (PARTITION BY TrainType ORDER BY TotalDistanceKm DESC) AS DistRank
FROM Train
ORDER BY TrainType, DistRank;

-- Query 13: Window Function (Running Cumulative Total)
-- Calculate daily revenue and running cumulative revenue collected on each booking date.
SELECT 
    PaymentDate,
    SUM(Amount) AS DailyRevenue,
    SUM(SUM(Amount)) OVER (ORDER BY PaymentDate ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) AS CumulativeRevenue
FROM Payment
WHERE PaymentStatus = 'Success'
GROUP BY PaymentDate
ORDER BY PaymentDate;

-- Query 14: Common Table Expression (CTE)
-- Count the number of scheduled stops per train and display trains with 3 or more stops.
WITH TrainStopCounts AS (
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
ORDER BY TotalStops DESC;

-- Query 15: Conditional Logic with CASE Statement
-- Categorize passengers into demographic brackets and count total bookings per bracket.
SELECT 
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
ORDER BY TotalSpent DESC;

-- Query 16: Self-Join
-- Find distinct pairs of different trains that originate from the same source station.
SELECT 
    s.StationName AS CommonSourceStation,
    t1.TrainNumber AS Train_1_Number,
    t1.TrainName AS Train_1_Name,
    t2.TrainNumber AS Train_2_Number,
    t2.TrainName AS Train_2_Name
FROM Train t1
INNER JOIN Train t2 ON t1.SourceStationID = t2.SourceStationID AND t1.TrainID < t2.TrainID
INNER JOIN Station s ON t1.SourceStationID = s.StationID;

-- Query 17: String and Pattern Matching
-- Find all passengers with a Gmail address whose contact starts with '98', formatting their full name.
SELECT 
    PassengerID,
    UPPER(FirstName || ' ' || LastName) AS FormattedName,
    PhoneNumber,
    Email
FROM Passenger
WHERE Email LIKE '%@gmail.com'
  AND PhoneNumber LIKE '98%'
ORDER BY PassengerID;

-- Query 18: View Creation and Manifest Querying
DROP VIEW IF EXISTS v_ConfirmedManifest;
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
ORDER BY SeatNumber;

-- Query 19: Data Modification (UPDATE with Subquery)
UPDATE Booking
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
WHERE p.Age >= 60;

-- Query 20: Complex Analytical Query - Coach Seat Occupancy Rate
SELECT 
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
ORDER BY t.TrainNumber, c.CoachNumber;
