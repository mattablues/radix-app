    public function testSetActiveAtParsesValidDateString(): void
    {
        $status = new Status();
        // Giltigt datum
        $dateString = '2023-05-01 12:00:00';
        
        // Använd strtotime för att få förväntat värde i samma tidszon som koden använder
        $expectedTimestamp = strtotime($dateString);
        
        $status->setActiveAtAttribute($dateString);
        
        // Verifiera att det sparades korrekt
        // Vi hämtar det råa attributet via getAttributes för att undvika accessorn som formaterar om det
        $attributes = $status->getAttributes();
        $this->assertSame($expectedTimestamp, $attributes['active_at']);
    }

    public function testGetActiveAtAttributeIsPublic(): void
    {
        $status = new Status();
        // Anropa metoden direkt för att verifiera att den är publik.
        // Mutanten som gör den protected kommer att orsaka ett fatal error.
        $this->assertNull($status->getActiveAtAttribute(null));
    }

    public function testSetActiveAtThrowsExceptionForZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Ogiltigt värde för active_at: 0');

        $status = new Status();
        // Originalkoden kräver > 0, så 0 ska kasta exception.
        // Mutanten (>= 0) skulle acceptera 0 och inte kasta exception.
        $status->setActiveAtAttribute(0);
    }
