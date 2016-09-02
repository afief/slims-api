
--
-- Struktur dari tabel `biblio_rate`
--

CREATE TABLE `biblio_rate` (
  `id` int(10) UNSIGNED NOT NULL,
  `biblio_id` int(10) UNSIGNED NOT NULL,
  `member_id` varchar(20) NOT NULL DEFAULT '',
  `rate` int(1) NOT NULL DEFAULT '1',
  `input_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `member_favorit`
--

CREATE TABLE `member_favorit` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `biblio_id` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `member_logins`
--

CREATE TABLE `member_logins` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `token` varchar(20) NOT NULL DEFAULT '',
  `ip` varchar(20) NOT NULL DEFAULT '',
  `browser` varchar(250) NOT NULL DEFAULT '',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Struktur dari tabel `member_message`
--

CREATE TABLE `member_message` (
  `id` int(10) UNSIGNED NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `from_id` varchar(20) NOT NULL,
  `text` varchar(250) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Struktur dari tabel `member_notification`
--

CREATE TABLE `member_notification` (
  `id` int(10) UNSIGNED NOT NULL,
  `to_id` varchar(20) NOT NULL,
  `from_id` varchar(20) NOT NULL,
  `text` varchar(250) NOT NULL,
  `param` varchar(20) NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `biblio_rate`
--
ALTER TABLE `biblio_rate`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_favorit`
--
ALTER TABLE `member_favorit`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_logins`
--
ALTER TABLE `member_logins`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_message`
--
ALTER TABLE `member_message`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `member_notification`
--
ALTER TABLE `member_notification`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `biblio_rate`
--
ALTER TABLE `biblio_rate`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `member_favorit`
--
ALTER TABLE `member_favorit`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
--
-- AUTO_INCREMENT for table `member_logins`
--
ALTER TABLE `member_logins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;
--
-- AUTO_INCREMENT for table `member_message`
--
ALTER TABLE `member_message`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `member_notification`
--
ALTER TABLE `member_notification`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;




  
--
-- Struktur dari tabel `member_report`
--

CREATE TABLE `member_report` (
  `id` int(11) NOT NULL,
  `member_id` varchar(20) NOT NULL,
  `text` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `member_report`
--
ALTER TABLE `member_report`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `member_report`
--
ALTER TABLE `member_report`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;