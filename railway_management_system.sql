-- =============================================================================
-- DATABASE SYSTEM: RAILWAY MANAGEMENT SYSTEM (RMS)
-- Compatible with SQLite, MySQL 8.0+, PostgreSQL 12+
-- =============================================================================

-- Drop tables if they already exist (in reverse dependency order)
DROP TABLE IF EXISTS Payment;
DROP TABLE IF EXISTS SeatReservation;
DROP TABLE IF EXISTS Booking;
DROP TABLE IF EXISTS Passenger;
DROP TABLE IF EXISTS Coach;
DROP TABLE IF EXISTS TrainSchedule;
DROP TABLE IF EXISTS Train;
DROP TABLE IF EXISTS Station;

-- -----------------------------------------------------------------------------
-- 1. STATION TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE Station (
    StationID INTEGER PRIMARY KEY,
    StationCode VARCHAR(10) NOT NULL UNIQUE,
    StationName VARCHAR(100) NOT NULL,
    City VARCHAR(50) NOT NULL,
    State VARCHAR(50) NOT NULL,
    PlatformsCount INTEGER NOT NULL CHECK (PlatformsCount > 0)
);

-- -----------------------------------------------------------------------------
-- 2. TRAIN TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE Train (
    TrainID INTEGER PRIMARY KEY,
    TrainNumber VARCHAR(10) NOT NULL UNIQUE,
    TrainName VARCHAR(100) NOT NULL,
    TrainType VARCHAR(30) NOT NULL CHECK (TrainType IN ('Express', 'Superfast', 'Passenger', 'Mail', 'Rajdhani')),
    SourceStationID INTEGER NOT NULL,
    DestinationStationID INTEGER NOT NULL,
    TotalDistanceKm INTEGER NOT NULL CHECK (TotalDistanceKm > 0),
    FOREIGN KEY (SourceStationID) REFERENCES Station(StationID) ON DELETE RESTRICT,
    FOREIGN KEY (DestinationStationID) REFERENCES Station(StationID) ON DELETE RESTRICT
);

-- -----------------------------------------------------------------------------
-- 3. TRAIN SCHEDULE TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE TrainSchedule (
    ScheduleID INTEGER PRIMARY KEY,
    TrainID INTEGER NOT NULL,
    StationID INTEGER NOT NULL,
    StopNumber INTEGER NOT NULL CHECK (StopNumber > 0),
    ArrivalTime TIME,
    DepartureTime TIME,
    DistanceFromSourceKm INTEGER NOT NULL CHECK (DistanceFromSourceKm >= 0),
    FOREIGN KEY (TrainID) REFERENCES Train(TrainID) ON DELETE CASCADE,
    FOREIGN KEY (StationID) REFERENCES Station(StationID) ON DELETE RESTRICT,
    UNIQUE (TrainID, StopNumber)
);

-- -----------------------------------------------------------------------------
-- 4. COACH TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE Coach (
    CoachID INTEGER PRIMARY KEY,
    TrainID INTEGER NOT NULL,
    CoachNumber VARCHAR(10) NOT NULL,
    CoachType VARCHAR(30) NOT NULL CHECK (CoachType IN ('AC 1-Tier', 'AC 2-Tier', 'AC 3-Tier', 'Sleeper', 'General')),
    TotalSeats INTEGER NOT NULL CHECK (TotalSeats > 0),
    FOREIGN KEY (TrainID) REFERENCES Train(TrainID) ON DELETE CASCADE,
    UNIQUE (TrainID, CoachNumber)
);

-- -----------------------------------------------------------------------------
-- 5. PASSENGER TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE Passenger (
    PassengerID INTEGER PRIMARY KEY,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Gender CHAR(1) NOT NULL CHECK (Gender IN ('M', 'F', 'O')),
    Age INTEGER NOT NULL CHECK (Age >= 0 AND Age <= 120),
    PhoneNumber VARCHAR(15) NOT NULL UNIQUE,
    Email VARCHAR(100) NOT NULL UNIQUE,
    City VARCHAR(50) NOT NULL
);

-- -----------------------------------------------------------------------------
-- 6. BOOKING TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE Booking (
    BookingID INTEGER PRIMARY KEY,
    PNR VARCHAR(10) NOT NULL UNIQUE,
    PassengerID INTEGER NOT NULL,
    TrainID INTEGER NOT NULL,
    JourneyDate DATE NOT NULL,
    BookingDate DATE NOT NULL,
    TotalFare DECIMAL(10,2) NOT NULL CHECK (TotalFare >= 0),
    BookingStatus VARCHAR(20) NOT NULL CHECK (BookingStatus IN ('Confirmed', 'Waiting', 'Cancelled')),
    FOREIGN KEY (PassengerID) REFERENCES Passenger(PassengerID) ON DELETE RESTRICT,
    FOREIGN KEY (TrainID) REFERENCES Train(TrainID) ON DELETE RESTRICT
);

-- -----------------------------------------------------------------------------
-- 7. SEAT RESERVATION TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE SeatReservation (
    ReservationID INTEGER PRIMARY KEY,
    BookingID INTEGER NOT NULL,
    CoachID INTEGER NOT NULL,
    SeatNumber INTEGER NOT NULL CHECK (SeatNumber > 0),
    BerthType VARCHAR(20) NOT NULL CHECK (BerthType IN ('Lower', 'Middle', 'Upper', 'Side Lower', 'Side Upper', 'Chair', 'Cabin')),
    Fare DECIMAL(10,2) NOT NULL CHECK (Fare >= 0),
    FOREIGN KEY (BookingID) REFERENCES Booking(BookingID) ON DELETE CASCADE,
    FOREIGN KEY (CoachID) REFERENCES Coach(CoachID) ON DELETE RESTRICT,
    UNIQUE (CoachID, SeatNumber)
);

-- -----------------------------------------------------------------------------
-- 8. PAYMENT TABLE
-- -----------------------------------------------------------------------------
CREATE TABLE Payment (
    PaymentID INTEGER PRIMARY KEY,
    BookingID INTEGER NOT NULL,
    PaymentDate DATE NOT NULL,
    Amount DECIMAL(10,2) NOT NULL CHECK (Amount >= 0),
    PaymentMethod VARCHAR(30) NOT NULL CHECK (PaymentMethod IN ('Credit Card', 'Debit Card', 'UPI', 'Net Banking')),
    PaymentStatus VARCHAR(20) NOT NULL CHECK (PaymentStatus IN ('Success', 'Failed', 'Refunded')),
    FOREIGN KEY (BookingID) REFERENCES Booking(BookingID) ON DELETE CASCADE
);

-- =============================================================================
-- SAMPLE DATA INSERTION
-- =============================================================================

-- STATIONS
INSERT INTO Station VALUES (1, 'NDLS', 'New Delhi Railway Station', 'New Delhi', 'Delhi', 16);
INSERT INTO Station VALUES (2, 'MMCT', 'Mumbai Central', 'Mumbai', 'Maharashtra', 9);
INSERT INTO Station VALUES (3, 'HWH', 'Howrah Junction', 'Kolkata', 'West Bengal', 23);
INSERT INTO Station VALUES (4, 'MAS', 'Chennai Central', 'Chennai', 'Tamil Nadu', 12);
INSERT INTO Station VALUES (5, 'SBC', 'Bengaluru City Junction', 'Bengaluru', 'Karnataka', 10);
INSERT INTO Station VALUES (6, 'JP', 'Jaipur Junction', 'Jaipur', 'Rajasthan', 8);
INSERT INTO Station VALUES (7, 'ADI', 'Ahmedabad Junction', 'Ahmedabad', 'Gujarat', 12);
INSERT INTO Station VALUES (8, 'PUNE', 'Pune Junction', 'Pune', 'Maharashtra', 6);
INSERT INTO Station VALUES (9, 'BSB', 'Varanasi Junction', 'Varanasi', 'Uttar Pradesh', 9);
INSERT INTO Station VALUES (10, 'CNB', 'Kanpur Central', 'Kanpur', 'Uttar Pradesh', 10);
INSERT INTO Station VALUES (11, 'GHY', 'Guwahati Junction', 'Guwahati', 'Assam', 7);

-- TRAINS
INSERT INTO Train VALUES (101, '12951', 'Mumbai Rajdhani Express', 'Rajdhani', 1, 2, 1386);
INSERT INTO Train VALUES (102, '12002', 'Bhopal Shatabdi', 'Superfast', 1, 10, 440);
INSERT INTO Train VALUES (103, '12301', 'Howrah Rajdhani', 'Rajdhani', 3, 1, 1450);
INSERT INTO Train VALUES (104, '12621', 'Tamil Nadu Express', 'Superfast', 4, 1, 2184);
INSERT INTO Train VALUES (105, '16533', 'Bangalore Mail', 'Mail', 5, 8, 1010);
INSERT INTO Train VALUES (106, '12958', 'Swarna Jayanti Rajdhani', 'Rajdhani', 7, 1, 935);

-- TRAIN SCHEDULES
INSERT INTO TrainSchedule VALUES (1, 101, 1, 1, NULL, '16:55:00', 0);
INSERT INTO TrainSchedule VALUES (2, 101, 6, 2, '21:30:00', '21:40:00', 308);
INSERT INTO TrainSchedule VALUES (3, 101, 7, 3, '03:15:00', '03:25:00', 890);
INSERT INTO TrainSchedule VALUES (4, 101, 2, 4, '08:35:00', NULL, 1386);

INSERT INTO TrainSchedule VALUES (5, 102, 1, 1, NULL, '06:00:00', 0);
INSERT INTO TrainSchedule VALUES (6, 102, 10, 2, '11:20:00', NULL, 440);

INSERT INTO TrainSchedule VALUES (7, 103, 3, 1, NULL, '16:50:00', 0);
INSERT INTO TrainSchedule VALUES (8, 103, 9, 2, '01:25:00', '01:35:00', 680);
INSERT INTO TrainSchedule VALUES (9, 103, 10, 3, '05:40:00', '05:45:00', 1015);
INSERT INTO TrainSchedule VALUES (10, 103, 1, 4, '10:05:00', NULL, 1450);

INSERT INTO TrainSchedule VALUES (11, 104, 4, 1, NULL, '22:00:00', 0);
INSERT INTO TrainSchedule VALUES (12, 104, 1, 2, '06:30:00', NULL, 2184);

INSERT INTO TrainSchedule VALUES (13, 105, 5, 1, NULL, '20:15:00', 0);
INSERT INTO TrainSchedule VALUES (14, 105, 8, 2, '14:20:00', NULL, 1010);

INSERT INTO TrainSchedule VALUES (15, 106, 7, 1, NULL, '17:45:00', 0);
INSERT INTO TrainSchedule VALUES (16, 106, 6, 2, '00:45:00', '00:55:00', 625);
INSERT INTO TrainSchedule VALUES (17, 106, 1, 3, '07:30:00', NULL, 935);

-- COACHES
INSERT INTO Coach VALUES (1, 101, 'H1', 'AC 1-Tier', 24);
INSERT INTO Coach VALUES (2, 101, 'A1', 'AC 2-Tier', 48);
INSERT INTO Coach VALUES (3, 101, 'B1', 'AC 3-Tier', 64);
INSERT INTO Coach VALUES (4, 102, 'C1', 'AC 3-Tier', 72);
INSERT INTO Coach VALUES (5, 103, 'H1', 'AC 1-Tier', 24);
INSERT INTO Coach VALUES (6, 103, 'B1', 'AC 3-Tier', 64);
INSERT INTO Coach VALUES (7, 104, 'S1', 'Sleeper', 72);
INSERT INTO Coach VALUES (8, 104, 'A1', 'AC 2-Tier', 48);
INSERT INTO Coach VALUES (9, 105, 'S1', 'Sleeper', 72);
INSERT INTO Coach VALUES (10, 106, 'B1', 'AC 3-Tier', 64);

-- PASSENGERS
INSERT INTO Passenger VALUES (1, 'Aarav', 'Sharma', 'M', 28, '9811223344', 'aarav.sharma@gmail.com', 'New Delhi');
INSERT INTO Passenger VALUES (2, 'Priya', 'Verma', 'F', 24, '9822334455', 'priya.verma@yahoo.com', 'Mumbai');
INSERT INTO Passenger VALUES (3, 'Rohan', 'Mehta', 'M', 35, '9833445566', 'rohan.mehta@gmail.com', 'Ahmedabad');
INSERT INTO Passenger VALUES (4, 'Ananya', 'Iyer', 'F', 62, '9844556677', 'ananya.iyer@gmail.com', 'Chennai');
INSERT INTO Passenger VALUES (5, 'Vikram', 'Singh', 'M', 45, '9855667788', 'vikram.singh@outlook.com', 'Jaipur');
INSERT INTO Passenger VALUES (6, 'Sneha', 'Mukherjee', 'F', 31, '9866778899', 'sneha.m@gmail.com', 'Kolkata');
INSERT INTO Passenger VALUES (7, 'Rajesh', 'Patil', 'M', 67, '9877889900', 'rajesh.patil@rediffmail.com', 'Pune');
INSERT INTO Passenger VALUES (8, 'Neha', 'Gupta', 'F', 19, '9888990011', 'neha.gupta@gmail.com', 'New Delhi');
INSERT INTO Passenger VALUES (9, 'Karan', 'Malhotra', 'M', 52, '9899001122', 'karan.m@gmail.com', 'New Delhi');
INSERT INTO Passenger VALUES (10, 'Sunita', 'Reddy', 'F', 58, '9800112233', 'sunita.reddy@gmail.com', 'Bengaluru');
INSERT INTO Passenger VALUES (11, 'Amitabh', 'Sen', 'M', 71, '9711223344', 'amitabh.sen@gmail.com', 'Kolkata');
INSERT INTO Passenger VALUES (12, 'Divya', 'Nair', 'F', 29, '9722334455', 'divya.nair@hotmail.com', 'Kochi');

-- BOOKINGS
INSERT INTO Booking VALUES (1001, 'PNR812301', 1, 101, '2026-12-01', '2026-11-15', 3850.00, 'Confirmed');
INSERT INTO Booking VALUES (1002, 'PNR812302', 2, 101, '2026-12-01', '2026-11-16', 2250.00, 'Confirmed');
INSERT INTO Booking VALUES (1003, 'PNR812303', 3, 106, '2026-12-05', '2026-11-18', 1650.00, 'Confirmed');
INSERT INTO Booking VALUES (1004, 'PNR812304', 4, 104, '2026-12-10', '2026-11-20', 2900.00, 'Confirmed');
INSERT INTO Booking VALUES (1005, 'PNR812305', 5, 101, '2026-12-02', '2026-11-10', 1450.00, 'Cancelled');
INSERT INTO Booking VALUES (1006, 'PNR812306', 6, 103, '2026-12-04', '2026-11-12', 4200.00, 'Confirmed');
INSERT INTO Booking VALUES (1007, 'PNR812307', 7, 105, '2026-12-08', '2026-11-21', 650.00, 'Confirmed');
INSERT INTO Booking VALUES (1008, 'PNR812308', 8, 102, '2026-12-03', '2026-11-19', 950.00, 'Confirmed');
INSERT INTO Booking VALUES (1009, 'PNR812309', 9, 101, '2026-12-01', '2026-11-22', 2250.00, 'Confirmed');
INSERT INTO Booking VALUES (1010, 'PNR812310', 10, 105, '2026-12-08', '2026-11-20', 650.00, 'Waiting');
INSERT INTO Booking VALUES (1011, 'PNR812311', 11, 103, '2026-12-04', '2026-11-14', 1850.00, 'Confirmed');
INSERT INTO Booking VALUES (1012, 'PNR812312', 1, 102, '2026-12-15', '2026-11-22', 950.00, 'Confirmed');
INSERT INTO Booking VALUES (1013, 'PNR812313', 3, 101, '2026-12-20', '2026-11-21', 3850.00, 'Confirmed');
INSERT INTO Booking VALUES (1014, 'PNR812314', 6, 104, '2026-12-22', '2026-11-20', 780.00, 'Cancelled');
INSERT INTO Booking VALUES (1015, 'PNR812315', 7, 105, '2026-12-25', '2026-11-22', 650.00, 'Waiting');

-- SEAT RESERVATIONS
INSERT INTO SeatReservation VALUES (501, 1001, 1, 4, 'Lower', 3850.00);
INSERT INTO SeatReservation VALUES (502, 1002, 2, 12, 'Side Lower', 2250.00);
INSERT INTO SeatReservation VALUES (503, 1003, 10, 21, 'Upper', 1650.00);
INSERT INTO SeatReservation VALUES (504, 1004, 8, 7, 'Lower', 2900.00);
INSERT INTO SeatReservation VALUES (505, 1006, 5, 2, 'Cabin', 4200.00);
INSERT INTO SeatReservation VALUES (506, 1007, 9, 34, 'Middle', 650.00);
INSERT INTO SeatReservation VALUES (507, 1008, 4, 18, 'Chair', 950.00);
INSERT INTO SeatReservation VALUES (508, 1009, 2, 15, 'Upper', 2250.00);
INSERT INTO SeatReservation VALUES (509, 1011, 6, 28, 'Side Upper', 1850.00);
INSERT INTO SeatReservation VALUES (510, 1012, 4, 19, 'Chair', 950.00);
INSERT INTO SeatReservation VALUES (511, 1013, 1, 5, 'Lower', 3850.00);

-- PAYMENTS
INSERT INTO Payment VALUES (9001, 1001, '2026-11-15', 3850.00, 'Credit Card', 'Success');
INSERT INTO Payment VALUES (9002, 1002, '2026-11-16', 2250.00, 'UPI', 'Success');
INSERT INTO Payment VALUES (9003, 1003, '2026-11-18', 1650.00, 'Net Banking', 'Success');
INSERT INTO Payment VALUES (9004, 1004, '2026-11-20', 2900.00, 'Debit Card', 'Success');
INSERT INTO Payment VALUES (9005, 1005, '2026-11-10', 1450.00, 'UPI', 'Refunded');
INSERT INTO Payment VALUES (9006, 1006, '2026-11-12', 4200.00, 'Credit Card', 'Success');
INSERT INTO Payment VALUES (9007, 1007, '2026-11-21', 650.00, 'UPI', 'Success');
INSERT INTO Payment VALUES (9008, 1008, '2026-11-19', 950.00, 'Debit Card', 'Success');
INSERT INTO Payment VALUES (9009, 1009, '2026-11-22', 2250.00, 'Credit Card', 'Success');
INSERT INTO Payment VALUES (9010, 1010, '2026-11-20', 650.00, 'UPI', 'Success');
INSERT INTO Payment VALUES (9011, 1011, '2026-11-14', 1850.00, 'Net Banking', 'Success');
INSERT INTO Payment VALUES (9012, 1012, '2026-11-22', 950.00, 'UPI', 'Success');
INSERT INTO Payment VALUES (9013, 1013, '2026-11-21', 3850.00, 'Credit Card', 'Success');
INSERT INTO Payment VALUES (9014, 1014, '2026-11-20', 780.00, 'Debit Card', 'Refunded');
INSERT INTO Payment VALUES (9015, 1015, '2026-11-22', 650.00, 'UPI', 'Failed');
