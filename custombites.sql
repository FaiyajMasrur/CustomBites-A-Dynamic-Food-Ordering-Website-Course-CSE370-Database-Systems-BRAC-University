-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2025 at 02:30 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `custombites`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `Admin_ID` int(11) NOT NULL,
  `Ingredient_ID` int(11) DEFAULT NULL,
  `Roles` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`Admin_ID`, `Ingredient_ID`, `Roles`) VALUES
(5, NULL, 'Manager');

-- --------------------------------------------------------

--
-- Table structure for table `based_on`
--

CREATE TABLE `based_on` (
  `Review_ID` int(11) NOT NULL,
  `Event_ID` int(11) DEFAULT NULL,
  `Order_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `based_on`
--

INSERT INTO `based_on` (`Review_ID`, `Event_ID`, `Order_ID`) VALUES
(2, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contains`
--

CREATE TABLE `contains` (
  `Item_ID` int(11) NOT NULL,
  `Order_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contains`
--

INSERT INTO `contains` (`Item_ID`, `Order_ID`) VALUES
(1, 2),
(1, 3),
(1, 5);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `Customer_ID` int(11) NOT NULL,
  `Credits` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`Customer_ID`, `Credits`) VALUES
(4, 0.00),
(6, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `discounts`
--

CREATE TABLE `discounts` (
  `Discount_ID` int(11) NOT NULL,
  `Code` varchar(50) NOT NULL,
  `Percentage` decimal(5,2) NOT NULL,
  `Expiry_Date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `Event_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`Event_ID`, `Name`, `Description`, `Status`) VALUES
(1, 'Couples Cook-Off', 'Pairs team up to cook their chosen dish with guidance from our expert staff', 'Scheduled'),
(2, 'Dough It Yourself', 'Create your own pizza completely from scratch.', 'Ongoing'),
(3, 'Burger Battle', 'Build your ultimate custom burger—best one takes home the prize!', 'Coming Soon'),
(4, 'Pasta fiesta', 'Dive into the world of creamy pastas, crafted with your personal twist', 'Coming Soon');

-- --------------------------------------------------------

--
-- Table structure for table `includes`
--

CREATE TABLE `includes` (
  `Order_ID` int(11) NOT NULL,
  `Discount_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `Ingredient_ID` int(11) NOT NULL,
  `Ingredient_Name` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `Item_ID` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`Item_ID`, `Name`, `Description`, `Price`) VALUES
(1, 'Classic Beef Burger', 'Juicy beef patty with lettuce, tomato, onion and our special sauce', 350.00),
(2, 'Cheese Burger', 'Beef patty topped with melted cheese, lettuce, tomato and mayo', 400.00),
(3, 'Chicken Cheese Burger', 'Grilled chicken breast with melted cheese and fresh vegetables', 380.00),
(4, 'Beef Naga Burger', 'Spicy beef patty with naga chili sauce, cheese and vegetables', 420.00),
(5, 'Chicken Tandoori Burger', 'Tandoori spiced chicken with mint sauce and fresh vegetables', 390.00),
(6, 'Fish Burger', 'Crispy fish fillet with tartar sauce and fresh vegetables', 370.00),
(7, 'Double Decker Burger', 'Two beef patties with double cheese, served with our special sauce', 550.00),
(8, 'Veggie Burger', 'Mixed vegetable patty with lettuce, tomato and special dressing', 320.00),
(9, 'Margherita Pizza', 'Classic pizza with tomato sauce, mozzarella cheese and fresh basil', 650.00),
(10, 'Pepperoni Pizza', 'Tomato sauce, mozzarella cheese and pepperoni slices', 750.00),
(11, 'Chicken Tikka Pizza', 'Spiced chicken tikka chunks with onions, peppers and cheese', 800.00),
(12, 'Beef Tehari Pizza', 'Fusion pizza with spiced beef tehari toppings and cheese', 850.00),
(13, 'Naga Lover Pizza', 'Chicken, beef, onions and spicy naga sauce for the brave hearts', 780.00),
(14, 'Seafood Pizza', 'A mix of prawns, fish and calamari with cheese and herbs', 900.00),
(15, 'BBQ Chicken Pizza', 'BBQ sauce base with grilled chicken, onions and cheese', 780.00),
(16, 'Vegetarian Pizza', 'A colorful mix of fresh vegetables with cheese', 700.00),
(17, 'Spaghetti Bolognese', 'Classic spaghetti with rich beef bolognese sauce and parmesan', 450.00),
(18, 'Chicken Alfredo', 'Fettuccine pasta with creamy alfredo sauce and grilled chicken', 480.00),
(19, 'Spicy Seafood Pasta', 'Mixed seafood with pasta in a spicy tomato sauce', 550.00),
(20, 'Masala Pasta', 'Fusion pasta with Indian spices, vegetables and chicken', 480.00),
(21, 'Mushroom Pasta', 'Creamy pasta with assorted mushrooms and herbs', 420.00),
(22, 'Prawn Pasta', 'Pasta tossed with prawns, garlic and white wine sauce', 520.00),
(23, 'Beef Pasta', 'Pasta with tender beef chunks in a rich tomato sauce', 490.00),
(24, 'Vegetable Pasta', 'Pasta with mixed vegetables in a light tomato sauce', 400.00),
(25, 'Chocolate Brownie', 'Rich chocolate brownie served with vanilla ice cream', 250.00),
(26, 'Cheesecake', 'Creamy New York style cheesecake with berry compote', 300.00),
(27, 'Mishti Doi', 'Traditional Bengali sweet yogurt dessert', 180.00),
(28, 'Rasmalai', 'Soft cottage cheese dumplings in sweetened, flavored milk', 220.00),
(29, 'Gulab Jamun', 'Deep-fried milk solids soaked in sugar syrup', 190.00),
(30, 'Firni', 'Rice pudding flavored with cardamom and pistachio', 180.00),
(31, 'Falooda', 'Rose syrup, vermicelli, and ice cream dessert drink', 260.00),
(32, 'Chom Chom', 'Traditional Bengali sweet made from milk solids', 200.00),
(33, 'Mango Lassi', 'Creamy yogurt drink blended with mango and a hint of cardamom', 150.00),
(34, 'Borhani', 'Traditional spicy yogurt drink with mint and coriander', 130.00),
(35, 'Lemonade', 'Fresh squeezed lemon with sugar syrup and mint', 120.00),
(36, 'Cold Coffee', 'Chilled coffee blended with ice cream', 180.00),
(37, 'Masala Tea', 'Traditional spiced tea with milk', 100.00),
(38, 'Fresh Orange Juice', 'Freshly squeezed orange juice', 160.00),
(39, 'Watermelon Juice', 'Refreshing watermelon juice', 150.00),
(40, 'Coca Cola', 'Classic cola served with ice', 90.00);

-- --------------------------------------------------------

--
-- Table structure for table `menu_items_category`
--

CREATE TABLE `menu_items_category` (
  `Category` varchar(50) NOT NULL,
  `Item_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_items_category`
--

INSERT INTO `menu_items_category` (`Category`, `Item_ID`) VALUES
('Burger', 1),
('Burger', 2),
('Burger', 3),
('Burger', 4),
('Burger', 5),
('Burger', 6),
('Burger', 7),
('Burger', 8),
('Desserts', 25),
('Desserts', 26),
('Desserts', 27),
('Desserts', 28),
('Desserts', 29),
('Desserts', 30),
('Desserts', 31),
('Desserts', 32),
('Drinks', 33),
('Drinks', 34),
('Drinks', 35),
('Drinks', 36),
('Drinks', 37),
('Drinks', 38),
('Drinks', 39),
('Drinks', 40),
('Pasta', 17),
('Pasta', 18),
('Pasta', 19),
('Pasta', 20),
('Pasta', 21),
('Pasta', 22),
('Pasta', 23),
('Pasta', 24),
('Pizza', 9),
('Pizza', 10),
('Pizza', 11),
('Pizza', 12),
('Pizza', 13),
('Pizza', 14),
('Pizza', 15),
('Pizza', 16);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `Customer_ID` int(11) NOT NULL,
  `Item_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Customer_ID`, `Item_ID`) VALUES
(4, 1);

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `Order_ID` int(11) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  `Status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`Order_ID`, `Subtotal`, `Status`) VALUES
(2, 350.00, 'unpaid'),
(3, 350.00, 'unpaid'),
(4, 350.00, 'paid'),
(5, 350.00, 'unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `order_details_customization`
--

CREATE TABLE `order_details_customization` (
  `Item_Name` varchar(100) NOT NULL,
  `Customizations` varchar(255) NOT NULL,
  `Order_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_details_items`
--

CREATE TABLE `order_details_items` (
  `Item_Name` varchar(100) NOT NULL,
  `Quantity` int(11) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Order_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details_items`
--

INSERT INTO `order_details_items` (`Item_Name`, `Quantity`, `Price`, `Order_ID`) VALUES
('Classic Beef Burger', 1, 350.00, 2),
('Classic Beef Burger', 1, 350.00, 4),
('Classic Beef Burger', 1, 350.00, 5);

-- --------------------------------------------------------

--
-- Table structure for table `participates`
--

CREATE TABLE `participates` (
  `Customer_ID` int(11) NOT NULL,
  `Review_ID` int(11) NOT NULL,
  `Event_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `Pay_ID` int(11) NOT NULL,
  `Customer_ID` int(11) NOT NULL,
  `Method` varchar(50) NOT NULL,
  `Item` varchar(100) NOT NULL,
  `Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `Review_ID` int(11) NOT NULL,
  `Date` date NOT NULL,
  `Comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`Review_ID`, `Date`, `Comment`) VALUES
(2, '2025-05-12', '10/10 Would Recommend.');

-- --------------------------------------------------------

--
-- Table structure for table `review_items`
--

CREATE TABLE `review_items` (
  `Items` varchar(100) NOT NULL,
  `Review_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `review_items`
--

INSERT INTO `review_items` (`Items`, `Review_ID`) VALUES
('Beef Naga Burger', 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_ID` int(11) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `First_name` varchar(50) NOT NULL,
  `Last_name` varchar(50) NOT NULL,
  `Address` varchar(255) DEFAULT NULL,
  `Contact` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_ID`, `Password`, `Email`, `First_name`, `Last_name`, `Address`, `Contact`) VALUES
(4, 'HardPass123', 'customer@example.com', 'Shahriar', 'Hossain', 'Mirpur-1, Dhaka', '12345678910'),
(5, 'HardPass456', 'admin@example.com', 'Faiyaj', 'Masrur', 'Shyamoli, Dhaka', '12345678911'),
(6, 'HardPass789', 'customer2@example.com', 'Hasibul', 'Alam', '60 feet, Mirpur-1', '12345678912');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`Admin_ID`),
  ADD KEY `Ingredient_ID` (`Ingredient_ID`);

--
-- Indexes for table `based_on`
--
ALTER TABLE `based_on`
  ADD PRIMARY KEY (`Review_ID`),
  ADD KEY `Event_ID` (`Event_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `contains`
--
ALTER TABLE `contains`
  ADD PRIMARY KEY (`Item_ID`,`Order_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`Customer_ID`);

--
-- Indexes for table `discounts`
--
ALTER TABLE `discounts`
  ADD PRIMARY KEY (`Discount_ID`),
  ADD UNIQUE KEY `Code` (`Code`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`Event_ID`);

--
-- Indexes for table `includes`
--
ALTER TABLE `includes`
  ADD PRIMARY KEY (`Order_ID`,`Discount_ID`),
  ADD KEY `Discount_ID` (`Discount_ID`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`Ingredient_ID`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`Item_ID`);

--
-- Indexes for table `menu_items_category`
--
ALTER TABLE `menu_items_category`
  ADD PRIMARY KEY (`Category`,`Item_ID`),
  ADD KEY `Item_ID` (`Item_ID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Customer_ID`),
  ADD KEY `Item_ID` (`Item_ID`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`Order_ID`);

--
-- Indexes for table `order_details_customization`
--
ALTER TABLE `order_details_customization`
  ADD PRIMARY KEY (`Item_Name`,`Customizations`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `order_details_items`
--
ALTER TABLE `order_details_items`
  ADD PRIMARY KEY (`Item_Name`,`Quantity`,`Price`,`Order_ID`),
  ADD KEY `Order_ID` (`Order_ID`);

--
-- Indexes for table `participates`
--
ALTER TABLE `participates`
  ADD PRIMARY KEY (`Customer_ID`,`Review_ID`,`Event_ID`),
  ADD KEY `Review_ID` (`Review_ID`),
  ADD KEY `Event_ID` (`Event_ID`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`Pay_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`Review_ID`);

--
-- Indexes for table `review_items`
--
ALTER TABLE `review_items`
  ADD PRIMARY KEY (`Items`),
  ADD KEY `Review_ID` (`Review_ID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_ID`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `discounts`
--
ALTER TABLE `discounts`
  MODIFY `Discount_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `Event_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `Ingredient_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `Item_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `Order_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `Pay_ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `Review_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_1` FOREIGN KEY (`Ingredient_ID`) REFERENCES `inventory` (`Ingredient_ID`) ON DELETE SET NULL;

--
-- Constraints for table `based_on`
--
ALTER TABLE `based_on`
  ADD CONSTRAINT `based_on_ibfk_1` FOREIGN KEY (`Review_ID`) REFERENCES `reviews` (`Review_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `based_on_ibfk_2` FOREIGN KEY (`Event_ID`) REFERENCES `events` (`Event_ID`) ON DELETE SET NULL,
  ADD CONSTRAINT `based_on_ibfk_3` FOREIGN KEY (`Order_ID`) REFERENCES `order_details` (`Order_ID`) ON DELETE SET NULL;

--
-- Constraints for table `contains`
--
ALTER TABLE `contains`
  ADD CONSTRAINT `contains_ibfk_1` FOREIGN KEY (`Item_ID`) REFERENCES `menu_items` (`Item_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `contains_ibfk_2` FOREIGN KEY (`Order_ID`) REFERENCES `order_details` (`Order_ID`) ON DELETE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `users` (`User_ID`) ON DELETE CASCADE;

--
-- Constraints for table `includes`
--
ALTER TABLE `includes`
  ADD CONSTRAINT `includes_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `order_details` (`Order_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `includes_ibfk_2` FOREIGN KEY (`Discount_ID`) REFERENCES `discounts` (`Discount_ID`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items_category`
--
ALTER TABLE `menu_items_category`
  ADD CONSTRAINT `menu_items_category_ibfk_1` FOREIGN KEY (`Item_ID`) REFERENCES `menu_items` (`Item_ID`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customers` (`Customer_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`Item_ID`) REFERENCES `menu_items` (`Item_ID`) ON DELETE SET NULL;

--
-- Constraints for table `order_details_customization`
--
ALTER TABLE `order_details_customization`
  ADD CONSTRAINT `order_details_customization_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `order_details` (`Order_ID`) ON DELETE CASCADE;

--
-- Constraints for table `order_details_items`
--
ALTER TABLE `order_details_items`
  ADD CONSTRAINT `order_details_items_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `order_details` (`Order_ID`) ON DELETE CASCADE;

--
-- Constraints for table `participates`
--
ALTER TABLE `participates`
  ADD CONSTRAINT `participates_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customers` (`Customer_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `participates_ibfk_2` FOREIGN KEY (`Review_ID`) REFERENCES `reviews` (`Review_ID`) ON DELETE CASCADE,
  ADD CONSTRAINT `participates_ibfk_3` FOREIGN KEY (`Event_ID`) REFERENCES `events` (`Event_ID`) ON DELETE CASCADE;

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`Customer_ID`) REFERENCES `customers` (`Customer_ID`) ON DELETE CASCADE;

--
-- Constraints for table `review_items`
--
ALTER TABLE `review_items`
  ADD CONSTRAINT `review_items_ibfk_1` FOREIGN KEY (`Review_ID`) REFERENCES `reviews` (`Review_ID`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
