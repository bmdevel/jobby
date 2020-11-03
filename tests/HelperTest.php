<?php

namespace Jobby\Tests;

use Jobby\Exception;
use Jobby\Helper;
use Jobby\InfoException;
use Jobby\Jobby;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass Jobby\Helper
 */
class HelperTest extends TestCase
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var string
     */
    private $tmpDir;

    /**
     * @var string
     */
    private $lockFile;

    /**
     * @var string
     */
    private $copyOfLockFile;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->helper = new Helper();
        $this->tmpDir = $this->helper->getTempDir();
        $this->lockFile = $this->tmpDir . '/test.lock';
        $this->copyOfLockFile = $this->tmpDir . "/test.lock.copy";
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($_SERVER['APPLICATION_ENV']);
    }

    /**
     * @param string $input
     * @param string $expected
     *
     * @dataProvider dataProviderTestEscape
     */
    public function testEscape($input, $expected)
    {
        $actual = $this->helper->escape($input);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function dataProviderTestEscape()
    {
        return [
            ['lower', 'lower'],
            ['UPPER', 'upper'],
            ['0123456789', '0123456789'],
            ['with    spaces', 'with_spaces'],
            ['invalid!@#$%^&*()chars', 'invalidchars'],
            ['._-', '._-'],
        ];
    }

    /**
     * @covers ::getPlatform
     */
    public function testGetPlatform()
    {
        $actual = $this->helper->getPlatform();
        $this->assertContains($actual, [Helper::UNIX, Helper::WINDOWS]);
    }

    /**
     * @covers ::getPlatform
     */
    public function testPlatformConstants()
    {
        $this->assertNotEquals(Helper::UNIX, Helper::WINDOWS);
    }

    /**
     * @covers ::acquireLock
     * @covers ::releaseLock
     */
    public function testAquireAndReleaseLock()
    {
        $this->markAsRisky();

        $this->helper->acquireLock($this->lockFile);
        $this->helper->releaseLock($this->lockFile);
        $this->helper->acquireLock($this->lockFile);
        $this->helper->releaseLock($this->lockFile);
    }

    /**
     * @covers ::acquireLock
     * @covers ::releaseLock
     */
    public function testLockFileShouldContainCurrentPid()
    {
        $this->helper->acquireLock($this->lockFile);

        //on Windows, file locking is mandatory not advisory, so you can't do file_get_contents on a locked file
        //therefore, we need to make a copy of the lock file in order to read its contents
        if ($this->helper->getPlatform() === Helper::WINDOWS) {
            copy($this->lockFile, $this->copyOfLockFile);
            $lockFile = $this->copyOfLockFile;
        } else {
            $lockFile = $this->lockFile;
        }

        $this->assertEquals(getmypid(), file_get_contents($lockFile));

        $this->helper->releaseLock($this->lockFile);
        $this->assertEmpty(file_get_contents($this->lockFile));
    }

    /**
     * @covers ::getLockLifetime
     */
    public function testLockLifetimeShouldBeZeroIfFileDoesNotExists()
    {
        unlink($this->lockFile);
        $this->assertFileNotExists($this->lockFile);
        $this->assertEquals(0, $this->helper->getLockLifetime($this->lockFile));
    }

    /**
     * @covers ::getLockLifetime
     */
    public function testLockLifetimeShouldBeZeroIfFileIsEmpty()
    {
        file_put_contents($this->lockFile, '');
        $this->assertEquals(0, $this->helper->getLockLifetime($this->lockFile));
    }

    /**
     * @covers ::getLockLifetime
     */
    public function testLockLifetimeShouldBeZeroIfItContainsAInvalidPid()
    {
        if ($this->helper->getPlatform() === Helper::WINDOWS) {
            $this->markTestSkipped("Test relies on posix_ functions");
        }

        file_put_contents($this->lockFile, 'invalid-pid');
        $this->assertEquals(0, $this->helper->getLockLifetime($this->lockFile));
    }

    /**
     * @covers ::getLockLifetime
     */
    public function testGetLocklifetime()
    {
        if ($this->helper->getPlatform() === Helper::WINDOWS) {
            $this->markTestSkipped("Test relies on posix_ functions");
        }

        $this->helper->acquireLock($this->lockFile);

        $this->assertEquals(0, $this->helper->getLockLifetime($this->lockFile));
        sleep(1);
        $this->assertEquals(1, $this->helper->getLockLifetime($this->lockFile));
        sleep(1);
        $this->assertEquals(2, $this->helper->getLockLifetime($this->lockFile));

        $this->helper->releaseLock($this->lockFile);
    }

    /**
     * @covers ::releaseLock
     */
    public function testReleaseNonExistin()
    {
        $this->expectException(Exception::class);
        $this->helper->releaseLock($this->lockFile);
    }

    /**
     * @covers ::acquireLock
     */
    public function testExceptionIfAquireFails()
    {
        $this->expectException(InfoException::class);

        $fh = fopen($this->lockFile, 'r+');
        $this->assertIsResource($fh);

        $res = flock($fh, LOCK_EX | LOCK_NB);
        $this->assertTrue($res);

        $this->helper->acquireLock($this->lockFile);
    }

    /**
     * @covers ::acquireLock
     */
    public function testAquireLockShouldFailOnSecondTry()
    {
        $this->expectException(Exception::class);

        $this->helper->acquireLock($this->lockFile);
        $this->helper->acquireLock($this->lockFile);
    }

    /**
     * @covers ::getTempDir
     */
    public function testGetTempDir()
    {
        $valid = [sys_get_temp_dir(), getcwd()];
        foreach (['TMP', 'TEMP', 'TMPDIR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $valid[] = $_SERVER[$key];
            }
        }

        $actual = $this->helper->getTempDir();
        $this->assertContains($actual, $valid);
    }

    /**
     * @covers ::getApplicationEnv
     */
    public function testGetApplicationEnv()
    {
        $_SERVER['APPLICATION_ENV'] = 'foo';

        $actual = $this->helper->getApplicationEnv();
        $this->assertEquals('foo', $actual);
    }

    /**
     * @covers ::getApplicationEnv
     */
    public function testGetApplicationEnvShouldBeNullIfUndefined()
    {
        $actual = $this->helper->getApplicationEnv();
        $this->assertNull($actual);
    }

    /**
     * @covers ::getHost
     */
    public function testGetHostname()
    {
        $actual = $this->helper->getHost();
        $this->assertContains($actual, [gethostname(), php_uname('n')]);
    }

    /**
     * @covers ::sendMail
     * @covers ::getCurrentMailer
     */
    public function testSendMail()
    {
        $mailer = $this->getSwiftMailerMock();
        $mailer->expects($this->once())
            ->method('send');

        $jobby = new Jobby();
        $config = $jobby->getDefaultConfig();
        $config['output'] = 'output message';
        $config['recipients'] = 'a@a.com,b@b.com';

        $helper = new Helper($mailer);
        $mail = $helper->sendMail('job', $config, 'message');

        $host = $helper->getHost();
        $email = "jobby@$host";
        $this->assertStringContainsString('job', $mail->getSubject());
        $this->assertStringContainsString("[$host]", $mail->getSubject());
        $this->assertCount(1, $mail->getFrom());
        $this->assertEquals('jobby', current($mail->getFrom()));
        $this->assertEquals($email, current(array_keys($mail->getFrom())));
        $this->assertEquals($email, current(array_keys($mail->getSender())));
        $this->assertStringContainsString($config['output'], $mail->getBody());
        $this->assertStringContainsString('message', $mail->getBody());
    }

    /**
     * @return \Swift_Mailer
     */
    private function getSwiftMailerMock()
    {
        return $this->createTestProxy('Swift_Mailer', [new \Swift_NullTransport()]);
    }

    /**
     * @return void
     */
    public function testItReturnsTheCorrectNullSystemDeviceForUnix()
    {
        $helper = $this->createPartialMock(Helper::class, ['getPlatform']);
        $helper
            ->expects($this->once())
            ->method('getPlatform')
            ->willReturn(Helper::UNIX);

        $this->assertEquals("/dev/null", $helper->getSystemNullDevice());
    }

    /**
     * @return void
     */
    public function testItReturnsTheCorrectNullSystemDeviceForWindows()
    {
        $helper = $this->createPartialMock(Helper::class, ['getPlatform']);
        $helper
            ->expects($this->once())
            ->method('getPlatform')
            ->willReturn(Helper::WINDOWS);

        $this->assertEquals("NUL", $helper->getSystemNullDevice());
    }
}
