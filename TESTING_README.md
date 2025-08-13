# IP Mapping System Testing Suite

This directory contains comprehensive testing tools for the IP mapping system. Use these scripts to validate functionality, test performance, and ensure reliability.

## 🧪 Testing Scripts Overview

### 1. **`test_ip_functions.php`** - Comprehensive CLI Test Suite
**Purpose**: Complete automated testing of all IP mapping functions
**Usage**: 
```bash
php test_ip_functions.php
```
**Features**:
- ✅ Tests all CRUD operations (Create, Read, Update, Delete)
- ✅ Validates input parsing for all formats
- ✅ Tests error handling and edge cases
- ✅ Automatic cleanup of test data
- ✅ Detailed pass/fail reporting

### 2. **`test_web_interface.php`** - Web-based Test Interface
**Purpose**: Browser-based testing with visual interface
**Access**: `http://your-domain/test_web_interface.php`
**Features**:
- 🖥️ User-friendly web interface
- 📊 Real-time test results display
- ☑️ Selective test category execution
- 🧹 Easy test data cleanup
- 📋 Manual testing guidelines

### 3. **`generate_test_data.php`** - Test Data Generator
**Purpose**: Create realistic test datasets for validation
**Access**: `http://your-domain/generate_test_data.php`
**Features**:
- 📦 Multiple dataset sizes (Small, Medium, Large, Edge Cases)
- 📈 Database statistics and monitoring
- 🏷️ Prefixed test data for easy identification
- 🧹 Built-in cleanup utilities

### 4. **`benchmark_performance.php`** - Performance Testing
**Purpose**: Measure system performance under various loads
**Usage**: 
```bash
php benchmark_performance.php
```
**Features**:
- ⚡ Tests all operation types (Add, Lookup, Update)
- 📊 Performance metrics and timing
- 💾 Memory usage analysis
- 📋 Comprehensive performance report

### 5. **`test_samples.php`** - Sample Input Validation
**Purpose**: Validate all documented sample inputs work correctly
**Usage**: 
```bash
php test_samples.php
```
**Features**:
- 📝 Tests all examples from documentation
- ✅ Validates admin panel samples
- 🔍 Tests all IP lookup formats
- ⚠️ Error handling verification

### 6. **`test_high_volume_lookups.php`** - High-Volume CLI Testing
**Purpose**: Stress test with thousands of IPs and comprehensive performance analysis
**Usage**: 
```bash
php test_high_volume_lookups.php
```
**Features**:
- 🚀 Tests 5000+ individual IP lookups
- 📦 Tests 2000+ bulk IP processing
- 🎭 Tests 1000+ mixed format entries
- ⚡ Comprehensive performance metrics
- 🏆 Detailed performance scoring
- 💾 Memory usage analysis

### 7. **`test_high_volume_web.php`** - High-Volume Web Testing
**Purpose**: Browser-based high-volume testing with visual progress
**Access**: `http://your-domain/test_high_volume_web.php`
**Features**:
- 🖥️ Visual progress indicators
- 📊 Real-time performance metrics
- 🔄 Live result updates
- 📈 Performance assessment graphs
- 🧹 Automatic cleanup

## 🚀 Quick Start Testing Guide

### Step 1: Basic Functionality Test
```bash
# Run the comprehensive test suite
php test_ip_functions.php
```
**Expected Result**: All tests should pass with ~90%+ success rate

### Step 2: Generate Test Data
1. Open `http://your-domain/generate_test_data.php`
2. Select "Small Dataset" 
3. Check "Use prefix" option
4. Click "Generate Test Data"

### Step 3: Web Interface Testing
1. Open `http://your-domain/test_web_interface.php`
2. Select all test categories
3. Click "Run Selected Tests"
4. Verify all tests pass in the web interface

### Step 4: Performance Validation
```bash
# Run performance benchmarks
php benchmark_performance.php
```
**Expected Result**: Operations should complete within acceptable time limits

### Step 5: Sample Input Validation
```bash
# Test all documented samples
php test_samples.php
```
**Expected Result**: All sample inputs should work as documented

### Step 6: High-Volume Performance Testing
```bash
# Run comprehensive high-volume test
php test_high_volume_lookups.php
```
**Expected Result**: 
- 90%+ success rate on all lookups
- 1000+ lookups per second performance
- Overall performance score 75%+

### Step 7: Web-Based High-Volume Testing
1. Open `http://your-domain/test_high_volume_web.php`
2. Click "Start High-Volume Test"
3. Monitor real-time progress and results
4. Verify performance metrics meet targets

## 📋 Test Categories Explained

### 🔧 Basic Operations
- **IP Range Addition**: Traditional start/end IP format
- **CIDR Addition**: Network block notation (e.g., 192.168.1.0/24)
- **IP List Addition**: Individual IPs and ranges with expansion
- **Update Operations**: Inline editing functionality
- **Delete Operations**: Range removal

### 🔍 Lookup Tests
- **Single IP Resolution**: Individual IP to team mapping
- **Bulk IP Resolution**: Multiple IPs in one query
- **CIDR Overlap Detection**: Finding teams within network blocks
- **Range Intersection**: Detecting overlapping ranges

### 📝 Parsing Tests
- **Input Format Detection**: Automatic format recognition
- **Mixed Input Handling**: Different formats in same query
- **Delimiter Support**: Space, comma, newline separation
- **Range Expansion**: Converting ranges to individual IPs

### ⚠️ Error Handling
- **Invalid IP Validation**: Malformed IP addresses
- **Invalid CIDR Validation**: Incorrect network notation
- **Boundary Conditions**: Edge cases and limits
- **Mixed Valid/Invalid**: Handling partial failures

### ⚡ Performance Tests
- **Bulk Operations**: Large dataset processing
- **Query Performance**: Lookup speed optimization
- **Memory Usage**: Resource consumption monitoring
- **Scalability**: Performance under load

## 🎯 Testing Best Practices

### Before Testing
1. **Backup Database**: Always backup before running tests
2. **Use Test Environment**: Don't run on production data
3. **Check Dependencies**: Ensure database connection works
4. **Clear Previous Tests**: Remove old test data

### During Testing
1. **Run Tests in Order**: Start with basic, then advanced
2. **Monitor Performance**: Watch for unusual delays
3. **Check Error Messages**: Read failure details carefully
4. **Validate Results**: Verify test data looks correct

### After Testing
1. **Cleanup Test Data**: Remove all test entries
2. **Review Results**: Analyze any failures
3. **Document Issues**: Record any problems found
4. **Update Code**: Fix any identified bugs

## 🔧 Sample Test Data

### Admin Panel Testing Samples

#### IP Range Mode:
```
Start IP: 192.168.100.1    End IP: 192.168.100.50    Team: Network Operations
Start IP: 10.50.0.1        End IP: 10.50.0.255       Team: Development Team
```

#### CIDR Mode:
```
CIDR: 172.16.0.0/24        Team: Security Team
CIDR: 10.0.0.0/16          Team: Infrastructure Team
CIDR: 192.168.1.0/28       Team: QA Team
```

#### IP List Mode:
```
IP List: 10.12.2.2 10.90.10.100 10.23.211.10           Team: DevOps Team
IP List: 203.0.113.1 203.0.113.5 203.0.113.10          Team: External Services
IP List: 10.10.10.10-10.10.10.20                       Team: Range Expansion Test
```

### IP Lookup Testing Samples

#### Single IPs:
```
192.168.100.25
10.50.0.100
172.16.0.50
```

#### Space-separated Lists:
```
192.168.100.1 10.50.0.1 172.16.0.1
10.12.2.2 10.90.10.100 10.23.211.10
```

#### CIDR Notation:
```
192.168.100.0/24
10.50.0.0/24
172.16.0.0/24
```

#### Mixed Formats:
```
192.168.100.25 10.50.0.0/24 172.16.0.1-172.16.0.50 10.12.2.2
```

## 🛠️ Troubleshooting

### Common Issues

#### "Database connection failed"
- Check `config/database.php` settings
- Verify MySQL/MariaDB is running
- Confirm database exists and is accessible

#### "Tests failing unexpectedly"
- Clear any existing test data first
- Check for database permission issues
- Verify PHP extensions are installed

#### "Performance tests too slow"
- Check database indexes are present
- Monitor system resources during tests
- Consider running on faster hardware

#### "Web interface not loading"
- Verify web server is running
- Check file permissions
- Ensure all required files are present

### Getting Help
1. **Check Error Messages**: Read the full error output
2. **Review Logs**: Check PHP and database error logs  
3. **Test Incrementally**: Run smaller tests first
4. **Database State**: Verify database schema is correct

## 📊 Expected Results

### Test Success Rates
- **Basic Operations**: 100% success expected
- **Lookup Tests**: 95%+ success expected  
- **Parsing Tests**: 100% success expected
- **Error Handling**: 100% success expected (errors caught properly)
- **Performance Tests**: Depends on hardware, but should complete

### Performance Benchmarks
- **IP Addition**: 100+ ranges/second
- **IP Lookup**: 1000+ lookups/second
- **Bulk Operations**: 50+ IPs/second
- **Memory Usage**: <50MB for typical operations

### Database Impact
- **Test Data Size**: Varies by dataset (20-500 ranges)
- **Storage Impact**: Minimal (few KB to MB)
- **Performance Impact**: Negligible with proper cleanup

## 🎉 Success Criteria

Your IP mapping system is working correctly if:
- ✅ All basic operation tests pass
- ✅ All sample inputs work as documented
- ✅ Performance meets expected benchmarks
- ✅ Error handling catches invalid inputs properly
- ✅ Web interface functions correctly
- ✅ Database cleanup works properly

## 📞 Support

If you encounter issues during testing:
1. Review this README thoroughly
2. Check all error messages carefully
3. Ensure you're following the testing sequence
4. Verify your system meets the requirements

Happy Testing! 🚀