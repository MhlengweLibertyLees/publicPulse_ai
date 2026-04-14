-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 14, 2026 at 01:49 PM
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
-- Database: `publicpulse_ai`
--

-- --------------------------------------------------------

--
-- Table structure for table `ai_insights`
--

CREATE TABLE `ai_insights` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('hotspot','trend','prediction','anomaly') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `severity` enum('info','warning','critical') NOT NULL DEFAULT 'info',
  `data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data_json`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ai_insights`
--

INSERT INTO `ai_insights` (`id`, `type`, `title`, `description`, `category_id`, `ward`, `severity`, `data_json`, `is_active`, `created_at`) VALUES
(1, 'hotspot', 'Water Crisis Hotspot: Ward 12', 'Ward 12 (Soshanguve Block X) has 5 water complaints in 30 days — 250% above the alert threshold. Infrastructure failure imminent.', 1, 'Ward 12', 'critical', '{\"complaint_count\":5,\"threshold\":3,\"ratio\":1.67}', 1, '2026-04-14 11:25:39'),
(2, 'trend', 'Roads Complaints Rising 40% Monthly', 'Road and transport complaints increased 40% over the past 30 days. Rainy season accelerating infrastructure deterioration.', 3, NULL, 'warning', '{\"growth_rate\":0.40,\"period\":\"30d\"}', 1, '2026-04-14 11:25:39'),
(3, 'prediction', 'Predicted: Water Failure in Ward 12', 'Based on 4 consecutive weeks of water outages, another failure in Ward 12 is predicted within 7 days. Confidence: 87%.', 1, 'Ward 12', 'critical', '{\"confidence\":0.87,\"predicted_date\":\"2024-12-10\",\"week_count\":4}', 1, '2026-04-14 11:25:39'),
(4, 'anomaly', 'Critical Complaint Spike: Nov 27–29', '6 critical complaints filed in 48 hours — the highest 48-hour spike in 90 days. Possible systemic infrastructure emergency.', NULL, NULL, 'critical', '{\"spike_count\":6,\"window\":\"48h\",\"avg_per_day\":1.2}', 1, '2026-04-14 11:25:39'),
(5, 'hotspot', 'Multi-Issue Hotspot: Soshanguve Block X', 'Block X shows clustering of water, electrical and safety complaints — evidence of systemic infrastructure failure in this zone.', 5, 'Ward 12', 'warning', '{\"categories\":[\"water\",\"electricity\",\"safety\"]}', 1, '2026-04-14 11:25:39'),
(6, 'trend', 'Resolution Rate Declining', 'Complaint resolution rate dropped from 68% to 51% over the past two months. Staff capacity or process bottleneck suspected.', NULL, NULL, 'warning', '{\"prev_rate\":0.68,\"curr_rate\":0.51}', 1, '2026-04-14 11:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `analytics_cache`
--

CREATE TABLE `analytics_cache` (
  `cache_key` varchar(120) NOT NULL,
  `cache_value` longtext NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `analytics_cache`
--

INSERT INTO `analytics_cache` (`cache_key`, `cache_value`, `expires_at`) VALUES
('ai_dashboard_insights', '[{\"id\":1,\"type\":\"hotspot\",\"title\":\"Water Crisis Hotspot: Ward 12\",\"description\":\"Ward 12 (Soshanguve Block X) has 5 water complaints in 30 days \\u2014 250% above the alert threshold. Infrastructure failure imminent.\",\"category_id\":1,\"ward\":\"Ward 12\",\"severity\":\"critical\",\"data_json\":\"{\\\"complaint_count\\\":5,\\\"threshold\\\":3,\\\"ratio\\\":1.67}\",\"is_active\":1,\"created_at\":\"2026-04-14 11:25:39\",\"category_name\":\"Water & Sanitation\",\"cat_icon\":\"droplet\"},{\"id\":3,\"type\":\"prediction\",\"title\":\"Predicted: Water Failure in Ward 12\",\"description\":\"Based on 4 consecutive weeks of water outages, another failure in Ward 12 is predicted within 7 days. Confidence: 87%.\",\"category_id\":1,\"ward\":\"Ward 12\",\"severity\":\"critical\",\"data_json\":\"{\\\"confidence\\\":0.87,\\\"predicted_date\\\":\\\"2024-12-10\\\",\\\"week_count\\\":4}\",\"is_active\":1,\"created_at\":\"2026-04-14 11:25:39\",\"category_name\":\"Water & Sanitation\",\"cat_icon\":\"droplet\"},{\"id\":4,\"type\":\"anomaly\",\"title\":\"Critical Complaint Spike: Nov 27\\u201329\",\"description\":\"6 critical complaints filed in 48 hours \\u2014 the highest 48-hour spike in 90 days. Possible systemic infrastructure emergency.\",\"category_id\":null,\"ward\":null,\"severity\":\"critical\",\"data_json\":\"{\\\"spike_count\\\":6,\\\"window\\\":\\\"48h\\\",\\\"avg_per_day\\\":1.2}\",\"is_active\":1,\"created_at\":\"2026-04-14 11:25:39\",\"category_name\":null,\"cat_icon\":null},{\"id\":2,\"type\":\"trend\",\"title\":\"Roads Complaints Rising 40% Monthly\",\"description\":\"Road and transport complaints increased 40% over the past 30 days. Rainy season accelerating infrastructure deterioration.\",\"category_id\":3,\"ward\":null,\"severity\":\"warning\",\"data_json\":\"{\\\"growth_rate\\\":0.40,\\\"period\\\":\\\"30d\\\"}\",\"is_active\":1,\"created_at\":\"2026-04-14 11:25:39\",\"category_name\":\"Roads & Transport\",\"cat_icon\":\"road\"},{\"id\":5,\"type\":\"hotspot\",\"title\":\"Multi-Issue Hotspot: Soshanguve Block X\",\"description\":\"Block X shows clustering of water, electrical and safety complaints \\u2014 evidence of systemic infrastructure failure in this zone.\",\"category_id\":5,\"ward\":\"Ward 12\",\"severity\":\"warning\",\"data_json\":\"{\\\"categories\\\":[\\\"water\\\",\\\"electricity\\\",\\\"safety\\\"]}\",\"is_active\":1,\"created_at\":\"2026-04-14 11:25:39\",\"category_name\":\"Public Safety\",\"cat_icon\":\"shield\"},{\"id\":6,\"type\":\"trend\",\"title\":\"Resolution Rate Declining\",\"description\":\"Complaint resolution rate dropped from 68% to 51% over the past two months. Staff capacity or process bottleneck suspected.\",\"category_id\":null,\"ward\":null,\"severity\":\"warning\",\"data_json\":\"{\\\"prev_rate\\\":0.68,\\\"curr_rate\\\":0.51}\",\"is_active\":1,\"created_at\":\"2026-04-14 11:25:39\",\"category_name\":null,\"cat_icon\":null}]', '2026-04-14 13:32:56'),
('kpi_overview', '{\"total\":30,\"open\":24,\"resolved\":5,\"critical\":10,\"today\":0,\"resRate\":16.7,\"avg_resolution_hours\":12920.5}', '2026-04-14 13:29:56');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'tag',
  `color` varchar(20) DEFAULT '#3b82f6',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `color`, `description`, `is_active`, `created_at`) VALUES
(1, 'Water & Sanitation', 'water', 'droplet', '#3b82f6', 'Water supply, sewage, drainage and sanitation issues', 1, '2026-04-14 11:25:39'),
(2, 'Electricity', 'electricity', 'bolt', '#f59e0b', 'Power outages, faulty infrastructure, billing disputes', 1, '2026-04-14 11:25:39'),
(3, 'Roads & Transport', 'roads', 'road', '#8b5cf6', 'Potholes, signage, road damage, public transport', 1, '2026-04-14 11:25:39'),
(4, 'Waste Management', 'waste', 'trash', '#10b981', 'Refuse collection, illegal dumping, recycling', 1, '2026-04-14 11:25:39'),
(5, 'Public Safety', 'safety', 'shield', '#ef4444', 'Crime, broken street lights, hazards, vandalism', 1, '2026-04-14 11:25:39'),
(6, 'Health Services', 'health', 'heart', '#ec4899', 'Clinics, hospitals, ambulance, health issues', 1, '2026-04-14 11:25:39'),
(7, 'Housing', 'housing', 'home', '#f97316', 'RDP housing, repairs, informal settlements', 1, '2026-04-14 11:25:39'),
(8, 'Education', 'education', 'book', '#06b6d4', 'Schools, libraries, bursaries, infrastructure', 1, '2026-04-14 11:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `id` int(10) UNSIGNED NOT NULL,
  `reference_no` varchar(20) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `status` enum('submitted','in_review','in_progress','resolved','closed') NOT NULL DEFAULT 'submitted',
  `priority` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `assigned_to` int(10) UNSIGNED DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `ai_score` tinyint(3) UNSIGNED DEFAULT NULL,
  `ai_flags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_flags`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`id`, `reference_no`, `user_id`, `category_id`, `title`, `description`, `location`, `latitude`, `longitude`, `ward`, `status`, `priority`, `assigned_to`, `image_path`, `ai_score`, `ai_flags`, `created_at`, `updated_at`) VALUES
(1, 'PP-2024-0001', 3, 1, 'No water for 3 days in Soshanguve Block X', 'Our entire street has had no water for 3 days. Children cannot wash and cooking is impossible.', 'Soshanguve Block X, Pretoria', -25.53290000, 28.09480000, 'Ward 12', 'resolved', 'high', NULL, NULL, 72, NULL, '2024-10-01 08:22:00', '2026-04-14 11:25:39'),
(2, 'PP-2024-0002', 4, 2, 'Power outage every evening in Mamelodi East', 'Load shedding beyond schedule — power off from 16:00 daily for 3 weeks. Appliances are being damaged.', 'Mamelodi East, Pretoria', -25.71460000, 28.37890000, 'Ward 7', 'in_progress', 'high', NULL, NULL, 65, NULL, '2024-10-03 10:05:00', '2026-04-14 11:25:39'),
(3, 'PP-2024-0003', 5, 3, 'Giant pothole on Jabulani Street — causing accidents', 'Dangerous pothole causing accidents. Two cars damaged this week. Emergency repair needed.', 'Atteridgeville, Pretoria', -25.79880000, 27.99940000, 'Ward 3', 'in_review', 'critical', NULL, NULL, 88, NULL, '2024-10-05 09:15:00', '2026-04-14 11:25:39'),
(4, 'PP-2024-0004', 3, 4, 'Illegal dumping near community park', 'Large illegal dump growing near kids play area. Health risk to children. Rats visible.', 'Ga-Rankuwa Zone 8, Pretoria', -25.61470000, 27.98580000, 'Ward 15', 'submitted', 'medium', NULL, NULL, 42, NULL, '2024-10-07 13:30:00', '2026-04-14 11:25:39'),
(5, 'PP-2024-0005', 4, 1, 'Sewage overflow on Mabopane Main Road', 'Raw sewage flowing in the street for 5 days. Terrible smell and serious health risk.', 'Mabopane, Pretoria', -25.58170000, 28.09820000, 'Ward 9', 'in_progress', 'critical', NULL, NULL, 95, NULL, '2024-10-09 07:45:00', '2026-04-14 11:25:39'),
(6, 'PP-2024-0006', 5, 5, 'All 8 street lights broken for 2 months', 'All 8 street lights on our road are broken. Crime has increased at night. Residents are afraid.', 'Eersterust, Pretoria', -25.74130000, 28.32260000, 'Ward 22', 'submitted', 'high', NULL, NULL, 78, NULL, '2024-10-11 18:00:00', '2026-04-14 11:25:39'),
(7, 'PP-2024-0007', 3, 6, 'Community clinic closed without notice', 'Community clinic was closed with no notice. Patients turned away including elderly and pregnant women.', 'Olivenhoutbosch, Pretoria', -25.89120000, 28.07450000, 'Ward 18', 'resolved', 'high', NULL, NULL, 60, NULL, '2024-10-13 11:20:00', '2026-04-14 11:25:39'),
(8, 'PP-2024-0008', 4, 3, 'No road signs at dangerous intersection', 'Intersection at Khumalo and Ntuli has no stop signs. Accidents happening daily. Child was hit last week.', 'Soshanguve Block K, Pretoria', -25.51980000, 28.11200000, 'Ward 12', 'submitted', 'critical', NULL, NULL, 90, NULL, '2024-10-15 08:00:00', '2026-04-14 11:25:39'),
(9, 'PP-2024-0009', 5, 1, 'Low water pressure in Lotus Gardens', 'Water pressure too low to fill tanks or use showers. Problem for 2 weeks now.', 'Lotus Gardens, Pretoria', -25.78900000, 28.34450000, 'Ward 5', 'in_review', 'medium', NULL, NULL, 44, NULL, '2024-10-17 14:10:00', '2026-04-14 11:25:39'),
(10, 'PP-2024-0010', 3, 4, 'Missed refuse collection for 3 weeks', 'Rubbish not collected in 3 weeks. Rats and pests are appearing. Health hazard.', 'Mamelodi West, Pretoria', -25.72130000, 28.36010000, 'Ward 7', 'in_progress', 'high', NULL, NULL, 68, NULL, '2024-10-19 09:55:00', '2026-04-14 11:25:39'),
(11, 'PP-2024-0011', 4, 2, 'Exposed electrical cables near primary school', 'Live wires hanging low near school gate. Children at serious risk of electrocution.', 'Soshanguve Block X, Pretoria', -25.53100000, 28.09720000, 'Ward 12', 'in_progress', 'critical', NULL, NULL, 97, NULL, '2024-10-21 07:30:00', '2026-04-14 11:25:39'),
(12, 'PP-2024-0012', 5, 7, 'RDP house roof leaking — walls damp', 'Government-issued house roof is leaking. Walls are damp and mould is growing. Family getting sick.', 'Mamelodi East, Pretoria', -25.71890000, 28.38120000, 'Ward 7', 'submitted', 'medium', NULL, NULL, 38, NULL, '2024-10-23 10:40:00', '2026-04-14 11:25:39'),
(13, 'PP-2024-0013', 3, 1, 'Burst water pipe flooding entire road', 'Burst municipal pipe flooding main road and surrounding houses. Three homes flooded.', 'Ga-Rankuwa Zone 4, Pretoria', -25.60980000, 27.99210000, 'Ward 15', 'resolved', 'critical', NULL, NULL, 85, NULL, '2024-10-25 06:15:00', '2026-04-14 11:25:39'),
(14, 'PP-2024-0014', 4, 8, 'Grade 4 class has no desks — children on floor', '45 children in Grade 4 sitting on the floor with no desks. Unacceptable learning conditions.', 'Atteridgeville, Pretoria', -25.80120000, 27.99880000, 'Ward 3', 'in_review', 'high', NULL, NULL, 62, NULL, '2024-10-27 09:00:00', '2026-04-14 11:25:39'),
(15, 'PP-2024-0015', 5, 3, 'Bridge crack poses imminent collapse risk', 'Visible crack in pedestrian bridge over the Apies River. Bridge used by hundreds daily.', 'Pretoria North', -25.67450000, 28.18760000, 'Ward 2', 'submitted', 'critical', NULL, NULL, 92, NULL, '2024-10-29 08:30:00', '2026-04-14 11:25:39'),
(16, 'PP-2024-0016', 3, 4, 'Stormwater drain blocked — flooding homes', 'Blocked drain floods homes every time it rains. Furniture destroyed twice this month.', 'Mabopane, Pretoria', -25.58340000, 28.10100000, 'Ward 9', 'in_progress', 'high', NULL, NULL, 74, NULL, '2024-11-01 13:00:00', '2026-04-14 11:25:39'),
(17, 'PP-2024-0017', 4, 5, 'Three armed robberies at taxi rank — no patrol', 'Three robberies in two weeks near main taxi rank. Police never respond. Commuters terrified.', 'Soshanguve Block X, Pretoria', -25.53450000, 28.09350000, 'Ward 12', 'submitted', 'high', NULL, NULL, 80, NULL, '2024-11-03 19:45:00', '2026-04-14 11:25:39'),
(18, 'PP-2024-0018', 5, 1, 'Water meter faulty — received R8000 bill', 'Municipal meter is clearly faulty. Received R8,000 water bill for one month. Impossible reading.', 'Eersterust, Pretoria', -25.73980000, 28.32410000, 'Ward 22', 'resolved', 'medium', NULL, NULL, 45, NULL, '2024-11-05 10:20:00', '2026-04-14 11:25:39'),
(19, 'PP-2024-0019', 3, 6, 'Ambulance never responded to emergency call', 'Called 10177 for cardiac arrest emergency. Waited 2 hours. No ambulance came. Neighbour died.', 'Mamelodi East, Pretoria', -25.71560000, 28.37980000, 'Ward 7', 'in_review', 'critical', NULL, NULL, 99, NULL, '2024-11-07 03:30:00', '2026-04-14 11:25:39'),
(20, 'PP-2024-0020', 4, 2, 'Prepaid electricity token system down 4 days', 'Prepaid electricity system down for 4 days. Cannot load tokens. Food in fridge spoiling.', 'Lotus Gardens, Pretoria', -25.79010000, 28.34120000, 'Ward 5', 'closed', 'medium', NULL, NULL, 30, NULL, '2024-11-09 11:15:00', '2026-04-14 11:25:39'),
(21, 'PP-2024-0021', 5, 1, 'Second water outage this month — Ward 12', 'Still no water. Second outage this month. Elderly and babies severely affected.', 'Soshanguve Block X, Pretoria', -25.53220000, 28.09610000, 'Ward 12', 'in_progress', 'critical', NULL, NULL, 96, NULL, '2024-11-11 08:00:00', '2026-04-14 11:25:39'),
(22, 'PP-2024-0022', 3, 4, 'Same illegal dump point — nothing done last report', 'Same illegal dump being used again. Previous report was ignored. Getting bigger.', 'Soshanguve Block X, Pretoria', -25.53380000, 28.09440000, 'Ward 12', 'submitted', 'medium', NULL, NULL, 48, NULL, '2024-11-13 14:00:00', '2026-04-14 11:25:39'),
(23, 'PP-2024-0023', 4, 3, 'Jabulani Street pothole still not fixed after 3 weeks', 'Pothole reported 3 weeks ago still not repaired. Now causing tyre damage daily.', 'Atteridgeville, Pretoria', -25.79980000, 27.99810000, 'Ward 3', 'in_review', 'high', NULL, NULL, 71, NULL, '2024-11-15 09:30:00', '2026-04-14 11:25:39'),
(24, 'PP-2024-0024', 5, 1, 'Water cuts every Tuesday — no notice given', 'Pattern observed — water always cut every Tuesday with no notice. Residents need to prepare.', 'Mamelodi East, Pretoria', -25.71340000, 28.37790000, 'Ward 7', 'submitted', 'high', NULL, NULL, 66, NULL, '2024-11-17 10:00:00', '2026-04-14 11:25:39'),
(25, 'PP-2024-0025', 3, 2, 'Power surge damaged 3 fridges on our street', 'Voltage spike damaged 3 fridges on our street. Eskom denies liability. R15,000 damage.', 'Mabopane, Pretoria', -25.58210000, 28.09980000, 'Ward 9', 'in_review', 'high', NULL, NULL, 73, NULL, '2024-11-19 16:00:00', '2026-04-14 11:25:39'),
(26, 'PP-2024-0026', 4, 5, 'Community park vandalised — benches broken', 'Community park has been vandalised. Benches broken, graffiti on walls, lights smashed.', 'Ga-Rankuwa Zone 8, Pretoria', -25.61340000, 27.98710000, 'Ward 15', 'resolved', 'low', NULL, NULL, 22, NULL, '2024-11-21 09:00:00', '2026-04-14 11:25:39'),
(27, 'PP-2024-0027', 5, 7, 'Informal settlement shacks are a fire risk', 'Shacks are too close together. One fire would spread through 50 homes instantly. Need spacing.', 'Olivenhoutbosch, Pretoria', -25.89010000, 28.07610000, 'Ward 18', 'in_review', 'critical', NULL, NULL, 87, NULL, '2024-11-23 11:45:00', '2026-04-14 11:25:39'),
(28, 'PP-2024-0028', 3, 8, 'Public library closed 6 weeks — no explanation', 'Public library has been closed for 6 weeks with no explanation. Students cannot study.', 'Pretoria North', -25.67120000, 28.18900000, 'Ward 2', 'submitted', 'medium', NULL, NULL, 40, NULL, '2024-11-25 10:30:00', '2026-04-14 11:25:39'),
(29, 'PP-2024-0029', 4, 1, 'Third water outage this month — Ward 12 infrastructure failing', 'Three water outages in Ward 12 this month alone. Infrastructure is clearly failing.', 'Soshanguve Block X, Pretoria', -25.53150000, 28.09520000, 'Ward 12', 'submitted', 'critical', NULL, NULL, 98, NULL, '2024-11-27 07:00:00', '2026-04-14 11:25:39'),
(30, 'PP-2024-0030', 5, 3, 'Road section collapsed after heavy rain near school', 'Heavy rains caused road section to collapse near primary school. Children cannot pass safely.', 'Mabopane, Pretoria', -25.58080000, 28.10230000, 'Ward 9', 'in_progress', 'critical', NULL, NULL, 94, NULL, '2024-11-29 06:30:00', '2026-04-14 11:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `complaint_id` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(60) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `complaint_id`, `type`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 1, 'status_update', 'Complaint PP-2024-0001 Updated', 'Status changed to Resolved. Water supply fully restored.', 1, '2026-04-14 11:25:39'),
(2, 3, 7, 'status_update', 'Complaint PP-2024-0007 Updated', 'Status changed to Resolved. Clinic has reopened.', 1, '2026-04-14 11:25:39'),
(3, 4, 2, 'status_update', 'Complaint PP-2024-0002 Updated', 'Status changed to In Progress. Technical team investigating.', 0, '2026-04-14 11:25:39'),
(4, 1, 5, 'new_complaint', 'New Critical Complaint: PP-2024-0005', 'Sewage overflow on Mabopane Main Road requires urgent attention.', 1, '2026-04-14 11:25:39'),
(5, 1, 11, 'new_complaint', 'New Critical Complaint: PP-2024-0011', 'Exposed electrical cables near primary school — CRITICAL.', 0, '2026-04-14 11:25:39'),
(6, 1, 19, 'new_complaint', 'New Critical Complaint: PP-2024-0019', 'Ambulance failed to respond to emergency. Critical case.', 0, '2026-04-14 11:25:39'),
(7, 1, 29, 'new_complaint', 'New Critical Complaint: PP-2024-0029', 'Third water outage this month in Ward 12.', 0, '2026-04-14 11:25:39'),
(8, 2, 3, 'status_update', 'New High Priority: PP-2024-0003', 'Critical pothole reported requiring immediate analysis.', 0, '2026-04-14 11:25:39'),
(9, 5, 13, 'status_update', 'Complaint PP-2024-0013 Resolved', 'Burst water pipe on your street has been repaired.', 0, '2026-04-14 11:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `generated_by` int(10) UNSIGNED NOT NULL,
  `report_type` enum('daily','weekly','monthly','custom') NOT NULL,
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `summary_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`summary_json`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `status_logs`
--

CREATE TABLE `status_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `complaint_id` int(10) UNSIGNED NOT NULL,
  `changed_by` int(10) UNSIGNED NOT NULL,
  `old_status` enum('submitted','in_review','in_progress','resolved','closed') DEFAULT NULL,
  `new_status` enum('submitted','in_review','in_progress','resolved','closed') NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `status_logs`
--

INSERT INTO `status_logs` (`id`, `complaint_id`, `changed_by`, `old_status`, `new_status`, `note`, `created_at`) VALUES
(1, 1, 1, 'submitted', 'in_review', 'Assigned to Ward 12 water technical team.', '2026-04-14 11:25:39'),
(2, 1, 1, 'in_review', 'in_progress', 'Pipe replacement crew dispatched.', '2026-04-14 11:25:39'),
(3, 1, 1, 'in_progress', 'resolved', 'Water supply fully restored. Main pipe repaired and pressure tested.', '2026-04-14 11:25:39'),
(4, 7, 1, 'submitted', 'in_review', 'Health Department notified. Investigating closure.', '2026-04-14 11:25:39'),
(5, 7, 1, 'in_review', 'resolved', 'Clinic reopened. Staff dispute resolved by HR department.', '2026-04-14 11:25:39'),
(6, 13, 1, 'submitted', 'in_progress', 'Emergency crew dispatched — critical pipe burst.', '2026-04-14 11:25:39'),
(7, 13, 1, 'in_progress', 'resolved', 'Pipe sealed. Road cleared. Water supply restored.', '2026-04-14 11:25:39'),
(8, 20, 1, 'submitted', 'in_review', 'Escalated to Eskom prepaid division.', '2026-04-14 11:25:39'),
(9, 20, 1, 'in_review', 'closed', 'Prepaid token system restored nationally by Eskom.', '2026-04-14 11:25:39'),
(10, 26, 1, 'submitted', 'in_review', 'Parks department notified for assessment.', '2026-04-14 11:25:39'),
(11, 26, 1, 'in_review', 'resolved', 'Park cleaned, new benches installed, lights repaired.', '2026-04-14 11:25:39'),
(12, 2, 1, 'submitted', 'in_review', 'Reported to City of Tshwane Electricity.', '2026-04-14 11:25:39'),
(13, 2, 1, 'in_review', 'in_progress', 'Technical team investigating load shedding schedule.', '2026-04-14 11:25:39'),
(14, 10, 1, 'submitted', 'in_review', 'Waste Management notified of missed collections.', '2026-04-14 11:25:39'),
(15, 10, 1, 'in_review', 'in_progress', 'Additional truck allocated to route. Collections resumed.', '2026-04-14 11:25:39');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('citizen','admin','analyst') NOT NULL DEFAULT 'citizen',
  `phone` varchar(20) DEFAULT NULL,
  `ward` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `ward`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'System Administrator', 'admin@publicpulse.gov.za', '$2y$10$KoRM9HuwgW/thScSZKlG8ee4OLlDmxqFCxpYGUwXPR7YCBKoc/2e6', 'admin', '0120001111', NULL, 1, '2026-04-14 11:25:39', '2026-04-14 13:27:35'),
(2, 'Data Analyst', 'analyst@publicpulse.gov.za', '$2y$10$eQwb.nKhnjuKXCiCBtBjvuglfIlRKK0XvUvWxm2NeJIXYNEaIfWIS', 'analyst', '0120002222', NULL, 1, '2026-04-14 11:25:39', '2026-04-14 13:40:28'),
(3, 'John Citizen', 'john@example.com', '$2y$10$vvIt9hluCOSvroaLIXKSduQdM69sOywhG7a0mMM9LjNW/GBRbbVpS', 'citizen', '0821234567', 'Ward 12', 1, '2026-04-14 11:25:39', '2026-04-14 13:43:07'),
(4, 'Mary Dlamini', 'mary@example.com', '$2y$10$2JK1tnu193EuNMqubYvoA.7CzYrI6Vqf4x0RA1L.AWFZdtLl.YdK.', 'citizen', '0837654321', 'Ward 7', 1, '2026-04-14 11:25:39', '2026-04-14 13:43:56'),
(5, 'Peter Nkosi', 'peter@example.com', '$2y$10$8djWlmqQr7FQLOHr2tMaaeXDvFRIRPA0pMzslUny9r5K3Idh3PGca', 'citizen', '0765432109', 'Ward 3', 1, '2026-04-14 11:25:39', '2026-04-14 13:45:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ai_insights`
--
ALTER TABLE `ai_insights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_is_active` (`is_active`),
  ADD KEY `fk_ai_cat` (`category_id`);

--
-- Indexes for table `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD PRIMARY KEY (`cache_key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_slug` (`slug`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reference_no` (`reference_no`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_ward` (`ward`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_coords` (`latitude`,`longitude`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rpt_user` (`generated_by`);

--
-- Indexes for table `status_logs`
--
ALTER TABLE `status_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_complaint_id` (`complaint_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `fk_sl_user` (`changed_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ai_insights`
--
ALTER TABLE `ai_insights`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `status_logs`
--
ALTER TABLE `status_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ai_insights`
--
ALTER TABLE `ai_insights`
  ADD CONSTRAINT `fk_ai_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `fk_c_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `fk_c_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_rpt_user` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `status_logs`
--
ALTER TABLE `status_logs`
  ADD CONSTRAINT `fk_sl_complaint` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sl_user` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
